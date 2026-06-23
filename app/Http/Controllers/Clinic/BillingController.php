<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\AppointmentStatus;
use App\Enums\ProcedureStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
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

        $appointment->load('procedures');
        $performed = $appointment->procedures->where('status', ProcedureStatus::Performed);

        if ($performed->isEmpty()) {
            return back()->with('error', 'No performed procedures to bill.');
        }

        $total = round((float) $performed->sum('price'), 2);

        // Bill reflects what was actually performed.
        $appointment->update([
            'total_amount' => $total,
            'status' => AppointmentStatus::Billed,
            'billed_at' => now(),
            'billed_by' => $request->user()->id,
        ]);

        $appointment->billingStatement()->updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'statement_no' => 'BS-'.now()->format('Ymd').'-'.str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT),
                'subtotal' => $total,
                'total' => $total,
                'created_by' => $request->user()->id,
                'issued_at' => now(),
            ]
        );

        return redirect()->route('clinic.appointments.show', $appointment)
            ->with('status', 'Billing statement created. The patient can now pay.');
    }
}
