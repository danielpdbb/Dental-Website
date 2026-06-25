<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreClinicAppointmentRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\ML\AppointmentFeatureExtractor;
use App\Services\ML\SchedulingModel;
use App\Services\PredictiveScheduler;
use App\Services\RewardService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Appointment::class);

        $appointments = Appointment::query()
            ->with(['patient', 'dentist', 'service', 'payments', 'procedures'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('dentist_id'), fn ($q) => $q->where('dentist_id', $request->integer('dentist_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('scheduled_at', $request->date('date')))
            ->orderByDesc('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        return view('clinic.appointments.index', [
            'appointments' => $appointments,
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'statuses' => AppointmentStatus::options(),
            'filters' => $request->only('status', 'dentist_id', 'date'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Appointment::class);

        return view('clinic.appointments.create', [
            'patients' => Patient::orderBy('last_name')->get(),
            'services' => Service::active()->orderBy('name')->get(),
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'prefill' => $request->only('dentist_id', 'service_id', 'scheduled_at', 'patient_id'),
        ]);
    }

    public function store(StoreClinicAppointmentRequest $request, PredictiveScheduler $scheduler): RedirectResponse
    {
        $services = Service::active()->whereIn('id', $request->validated('service_ids'))->get();
        $dentist = User::findOrFail($request->integer('dentist_id'));
        $isWalkIn = $request->boolean('is_walk_in');
        $start = Carbon::parse($request->date('scheduled_at'));
        $duration = max(15, (int) $services->sum('duration_minutes'));

        // Regular bookings must honour clinic hours; walk-ins are immediate.
        if (! $isWalkIn && ($error = $this->slotProblem($start, $duration))) {
            return back()->withInput()->withErrors(['scheduled_at' => $error]);
        }
        if (! $scheduler->isSlotAvailable($dentist, $start, $duration)) {
            return back()->withInput()->withErrors(['scheduled_at' => 'That dentist is already booked at that time.']);
        }

        // Existing patient, or create a quick walk-in patient record.
        $patient = $request->filled('patient_id')
            ? Patient::findOrFail($request->integer('patient_id'))
            : Patient::create([
                'first_name' => $request->input('new_first_name'),
                'last_name' => $request->input('new_last_name', ''),
                'phone' => $request->input('new_phone'),
            ]);

        $appointment = $patient->appointments()->create([
            'dentist_id' => $dentist->id,
            'service_id' => $services->first()->id, // primary service (back-compat)
            'scheduled_at' => $start,
            'duration_minutes' => $duration,
            'total_amount' => round((float) $services->sum('price'), 2),
            'status' => AppointmentStatus::Booked,
            'is_walk_in' => $isWalkIn,
            'notes' => $request->input('notes'),
            'created_by' => $request->user()->id,
        ]);

        foreach ($services as $service) {
            $appointment->procedures()->create([
                'service_id' => $service->id,
                'procedure_name' => $service->name,
                'price' => $service->price,
                'duration_minutes' => $service->duration_minutes,
                'status' => \App\Enums\ProcedureStatus::Planned,
            ]);
        }

        // Confirm to the patient if they have a portal account.
        $patient->user?->notify(new \App\Notifications\PatientAlert(
            'Appointment confirmed',
            'Your appointment with '.$dentist->name.' is booked for '.$start->format('l, M j · g:i A').'.',
            route('portal.appointments.index'),
            email: true,
        ));

        return redirect()->route('clinic.appointments.index')
            ->with('status', 'Appointment created.');
    }

    public function show(Appointment $appointment, RewardService $rewards, SchedulingModel $model, AppointmentFeatureExtractor $extractor): View
    {
        $this->authorize('view', $appointment);

        $appointment->load(['patient.user', 'dentist', 'service', 'payments.recorder', 'creator', 'canceller', 'procedures.service', 'procedures.performer', 'billingStatement.items', 'recommendations.service']);

        $patientUser = $appointment->patient?->user;

        // No-show risk (Decision Tree) — only meaningful for upcoming bookings.
        $risk = null;
        if ($appointment->status === AppointmentStatus::Booked) {
            $keep = $model->keepProbability($extractor->appointmentVector($appointment));
            if ($keep !== null) {
                [$label, $classes] = $model->riskBadge($keep);
                $risk = ['keep' => $keep, 'label' => $label, 'classes' => $classes];
            }
        }

        return view('clinic.appointments.show', [
            'appointment' => $appointment,
            'methods' => PaymentMethod::manualOptions(),
            'paymentStatuses' => PaymentStatus::options(),
            'rewardPoints' => $patientUser ? $rewards->pointsBalance($patientUser) : 0,
            'rewardMax' => $patientUser ? $rewards->maxRedeemablePeso($patientUser, $appointment) : 0.0,
            'risk' => $risk,
        ]);
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('cancel', $appointment);

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'cancelled_by' => $request->user()->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->input('reason'),
        ]);

        return back()->with('status', 'Appointment cancelled.');
    }

    public function complete(Appointment $appointment, RewardService $rewards): RedirectResponse
    {
        $this->authorize('updateStatus', $appointment);
        $appointment->update(['status' => AppointmentStatus::Completed]);

        // A completed visit can turn a pending "refer a friend" sign-up into a
        // rewarded one (idempotent — no-ops if there's nothing to reward).
        $rewards->checkQualification($appointment->patient?->user);

        return back()->with('status', 'Appointment marked completed.');
    }

    public function noShow(Appointment $appointment): RedirectResponse
    {
        $this->authorize('updateStatus', $appointment);
        $appointment->update(['status' => AppointmentStatus::NoShow]);

        return back()->with('status', 'Appointment marked as no-show.');
    }

    public function reschedule(Request $request, Appointment $appointment, PredictiveScheduler $scheduler): RedirectResponse
    {
        $this->authorize('reschedule', $appointment);

        $request->validate(['scheduled_at' => ['required', 'date']]);
        $start = Carbon::parse($request->date('scheduled_at'));

        if ($error = $this->slotProblem($start, $appointment->duration_minutes)) {
            return back()->withErrors(['scheduled_at' => $error]);
        }
        if (! $scheduler->isSlotAvailable($appointment->dentist, $start, $appointment->duration_minutes, $appointment->id)) {
            return back()->withErrors(['scheduled_at' => 'That dentist is already booked at that time.']);
        }

        $appointment->update(['scheduled_at' => $start]);

        return back()->with('status', 'Appointment rescheduled to '.$start->format('M j, Y g:i A').'.');
    }

    private function slotProblem(Carbon $start, int $duration): ?string
    {
        if ($start->isPast()) {
            return 'Please choose a future date and time.';
        }
        if (! in_array($start->isoWeekday(), config('clinic.open_days'), true)) {
            return 'The clinic is closed on that day.';
        }
        $open = $start->copy()->setTimeFromTimeString(config('clinic.open_time'));
        $close = $start->copy()->setTimeFromTimeString(config('clinic.close_time'));
        if ($start->lt($open) || $start->copy()->addMinutes($duration)->gt($close)) {
            return 'Please choose a time within clinic hours ('.config('clinic.open_time').'–'.config('clinic.close_time').').';
        }

        return null;
    }
}
