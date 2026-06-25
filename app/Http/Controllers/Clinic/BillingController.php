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
     * Printable billing statement (pre-payment) or official invoice (once fully paid).
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

        $appointment->load(['patient', 'dentist', 'payments']);
        $statement->load('items');

        return Pdf::loadView('clinic.billing.document', [
            'appointment' => $appointment,
            'statement' => $statement,
            'type' => $type,
        ])->stream(strtoupper($type).'-'.$appointment->id.'.pdf');
    }
}
