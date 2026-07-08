<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\AppointmentStatus;
use App\Enums\ProcedureStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Receptionist billing: a queue of dentist-endorsed sessions, and the action that
 * turns one into a billing statement the patient then pays.
 */
class BillingController extends Controller
{
    public function index(): View
    {
        $forBilling = Appointment::where('status', AppointmentStatus::ForBilling->value)
            ->with(['patient', 'dentist', 'procedures', 'endorser'])
            ->orderBy('endorsed_at')
            ->paginate(20);

        return view('clinic.billing.index', ['appointments' => $forBilling]);
    }

    /**
     * Create the billing statement from the performed procedures → status Billed.
     */
    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('bill', $appointment);

        abort_unless($appointment->status === AppointmentStatus::ForBilling, 403, 'This appointment is not awaiting billing.');

        $data = $request->validate([
            'discount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $appointment->load('procedures');
        $performed = $appointment->procedures->where('status', ProcedureStatus::Performed);

        if ($performed->isEmpty()) {
            return back()->with('error', 'No performed procedures to bill.');
        }

        $subtotal = round((float) $performed->sum('price'), 2);
        $discount = round((float) ($data['discount'] ?? 0), 2);
        $total = max(0, round($subtotal - $discount, 2));

        // FOLLOW-UP: if this visit follows a previous one that already has a statement,
        // consolidate — the new procedures are APPENDED to the original statement so the
        // patient keeps ONE bill/invoice (e.g. braces + every adjustment), and payments
        // continue against the original appointment.
        $parent = $appointment->parent_appointment_id
            ? $appointment->parent()->with(['billingStatement', 'payments'])->first()
            : null;

        if ($parent && $parent->billingStatement) {
            return $this->mergeIntoParent($request, $appointment, $parent, $performed, $subtotal, $discount, $total, $data['notes'] ?? null);
        }

        // Bill reflects what was actually performed.
        $appointment->update([
            'total_amount' => $total,
            'status' => AppointmentStatus::Billed,
            'billed_at' => now(),
            'billed_by' => $request->user()->id,
        ]);

        $statement = $appointment->billingStatement()->updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'statement_no' => 'BS-'.now()->format('Ymd').'-'.str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
                'issued_at' => now(),
            ]
        );

        // Freeze the itemised line snapshot so the patient sees exactly what they pay for.
        $statement->items()->delete();
        foreach ($performed as $proc) {
            $statement->items()->create([
                'appointment_procedure_id' => $proc->id,
                'description' => $proc->procedure_name,
                'quantity' => 1,
                'unit_price' => $proc->price,
                'line_total' => $proc->price,
            ]);
        }

        // Tell the patient their bill is ready (bell + email).
        $appointment->patient?->user?->notify(new \App\Notifications\PatientAlert(
            'Your bill is ready',
            'Your bill for the '.$appointment->scheduled_at->format('M j').' visit is ₱'.number_format($total, 2).'. You can review the itemised bill and pay online.',
            route('portal.appointments.index'),
            email: true,
        ));

        return redirect()->route('clinic.appointments.show', $appointment)
            ->with('status', 'Itemised billing statement created. The patient can now review and pay.');
    }

    /**
     * Consolidate a follow-up visit onto the ORIGINAL appointment's statement: append
     * its performed procedures as new line items, grow the parent's balance, and close
     * the follow-up itself (it owes nothing directly — the parent carries the account).
     */
    private function mergeIntoParent(Request $request, Appointment $appointment, Appointment $parent, $performed, float $subtotal, float $discount, float $total, ?string $notes): RedirectResponse
    {
        $statement = $parent->billingStatement;

        foreach ($performed as $proc) {
            $statement->items()->create([
                'appointment_procedure_id' => $proc->id,
                'description' => $proc->procedure_name.' ('.$appointment->scheduled_at->format('M j').' follow-up)',
                'quantity' => 1,
                'unit_price' => $proc->price,
                'line_total' => $proc->price,
            ]);
        }

        // New charges re-open the account: totals grow, any earlier "paid in full"
        // invoice stamp is cleared until the new balance is settled.
        $statement->update([
            'subtotal' => round((float) $statement->subtotal + $subtotal, 2),
            'discount' => round((float) $statement->discount + $discount, 2),
            'total' => round((float) $statement->total + $total, 2),
            'notes' => trim(($statement->notes ? $statement->notes."\n" : '').($notes ?? '')) ?: $statement->notes,
            'invoice_no' => null,
            'paid_at' => null,
        ]);

        $parent->update([
            'total_amount' => round((float) $parent->total_amount + $total, 2),
            'status' => AppointmentStatus::Billed,
        ]);

        // The follow-up visit itself carries no separate bill.
        $appointment->update([
            'total_amount' => 0,
            'status' => AppointmentStatus::Completed,
            'billed_at' => now(),
            'billed_by' => $request->user()->id,
        ]);

        $appointment->patient?->user?->notify(new \App\Notifications\PatientAlert(
            'Follow-up added to your bill',
            'Your '.$appointment->scheduled_at->format('M j').' follow-up (₱'.number_format($total, 2).') was added to statement '.$statement->statement_no.'. Outstanding balance: ₱'.number_format($parent->fresh()->balance(), 2).'.',
            route('portal.appointments.index'),
            email: true,
        ));

        return redirect()->route('clinic.appointments.show', $parent)
            ->with('status', 'Follow-up consolidated into statement '.$statement->statement_no.' — one bill, one invoice. New balance: ₱'.number_format($parent->fresh()->balance(), 2).'.');
    }

    /**
     * Printable billing statement (pre-payment) or official invoice (once fully paid).
     *
     * Optional ?visit={appointmentId} isolates ONE visit's slice of a consolidated,
     * multi-follow-up statement — "what did THIS visit add, what was already owed
     * before it, and what's been paid since" — so a patient reading a follow-up's
     * paperwork isn't confused by the full historical total.
     */
    public function print(Request $request, Appointment $appointment, string $type)
    {
        $this->authorize('view', $appointment);
        abort_unless(in_array($type, ['bill', 'invoice'], true), 404);

        $statement = $appointment->billingStatement;
        abort_unless($statement, 404, 'No billing statement for this appointment.');

        if ($type === 'invoice') {
            abort_unless($statement->invoice_no, 403, 'An invoice is available once the bill is fully paid.');
        }

        $appointment->load(['patient', 'dentist', 'payments', 'followUps']);
        $statement->load('items.procedure');

        $visitSummary = null;
        $visitId = $request->integer('visit') ?: null;
        if ($visitId) {
            $visit = $visitId === $appointment->id ? $appointment : $appointment->followUps->firstWhere('id', $visitId);
            if ($visit) {
                $visitSummary = $this->visitSummary($appointment, $statement, $visit);
            }
        }

        return Pdf::loadView('clinic.billing.document', [
            'appointment' => $appointment,
            'statement' => $statement,
            'type' => $type,
            'visitSummary' => $visitSummary,
        ])->stream(strtoupper($type).'-'.$appointment->id.($visitId ? '-visit'.$visitId : '').'.pdf');
    }

    /**
     * Isolate one visit's slice of a consolidated statement: what was already owed
     * before this visit, what this visit newly charged, and what's been paid since.
     *
     * @return array{visit: Appointment, broughtForward: float, thisVisitCharges: float, paidThisVisit: float, remaining: float}
     */
    private function visitSummary(Appointment $parent, $statement, Appointment $visit): array
    {
        $thisVisitItems = $statement->items->filter(fn ($i) => $i->procedure?->appointment_id === $visit->id);
        $thisVisitCharges = round((float) $thisVisitItems->sum('line_total'), 2);
        $priorCharges = round((float) $statement->total - $thisVisitCharges, 2);

        $paidBefore = round((float) $parent->payments
            ->where('status', \App\Enums\PaymentStatus::Paid)
            ->filter(fn ($p) => $p->paid_at && $p->paid_at->lt($visit->scheduled_at))
            ->sum('amount'), 2);
        $paidSince = round((float) $parent->payments
            ->where('status', \App\Enums\PaymentStatus::Paid)
            ->filter(fn ($p) => $p->paid_at && $p->paid_at->gte($visit->scheduled_at))
            ->sum('amount'), 2);

        return [
            'visit' => $visit,
            'broughtForward' => max(0, round($priorCharges - $paidBefore, 2)),
            'thisVisitCharges' => $thisVisitCharges,
            'paidThisVisit' => $paidSince,
            'remaining' => max(0, round((float) $statement->total - $parent->amountPaid(), 2)),
        ];
    }
}
