<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\BookAppointmentRequest;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use App\Services\PredictiveScheduler;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $patient = RecordController::resolvePatient($request->user());

        return view('portal.appointments.index', [
            'upcoming' => $patient->appointments()->upcoming()->with(['service', 'dentist', 'payments'])->orderBy('scheduled_at')->get(),
            'past' => $patient->appointments()
                ->where(fn ($q) => $q->where('scheduled_at', '<', now())
                    ->orWhereIn('status', ['completed', 'cancelled', 'no_show']))
                ->with(['service', 'dentist', 'payments'])->latest('scheduled_at')->get(),
            'outstanding' => $patient->appointments()->with('payments')->get()
                ->sum(fn ($a) => $a->balance()),
        ]);
    }

    public function create(Request $request, PredictiveScheduler $scheduler): View
    {
        $service = $request->filled('service_id') ? Service::active()->find($request->integer('service_id')) : null;
        $dentist = $request->filled('dentist_id') ? User::where('role', UserRole::Dentist)->find($request->integer('dentist_id')) : null;
        $date = $request->filled('date') ? Carbon::parse($request->date('date')) : Carbon::today();

        // Only build the time grid once a service + dentist are chosen.
        $slots = ($service && $dentist)
            ? $scheduler->daySlots($dentist, $service->duration_minutes, $date)
            : null;

        return view('portal.appointments.create', [
            'services' => Service::active()->orderBy('name')->get(),
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'service' => $service,
            'dentist' => $dentist,
            'date' => $date,
            'slots' => $slots,
        ]);
    }

    public function store(BookAppointmentRequest $request, PredictiveScheduler $scheduler): RedirectResponse
    {
        $patient = RecordController::resolvePatient($request->user());
        $service = Service::findOrFail($request->integer('service_id'));
        $dentist = User::findOrFail($request->integer('dentist_id'));
        $start = Carbon::parse($request->date('scheduled_at'));

        if ($error = $this->slotProblem($scheduler, $dentist, $start, $service->duration_minutes)) {
            return back()->withInput()->withErrors(['scheduled_at' => $error]);
        }

        $patient->appointments()->create([
            'dentist_id' => $dentist->id,
            'service_id' => $service->id,
            'scheduled_at' => $start,
            'duration_minutes' => $service->duration_minutes,
            'total_amount' => $service->price,
            'status' => AppointmentStatus::Booked,
            'notes' => $request->input('notes'),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('portal.appointments.index')
            ->with('status', 'Appointment booked for '.$start->format('M j, Y g:i A').'.');
    }

    public function reschedule(Request $request, Appointment $appointment, PredictiveScheduler $scheduler): View
    {
        $this->authorize('reschedule', $appointment);
        $appointment->load(['service', 'dentist']);

        $date = $request->filled('date') ? Carbon::parse($request->date('date')) : $appointment->scheduled_at->copy();

        return view('portal.appointments.reschedule', [
            'appointment' => $appointment,
            'date' => $date,
            'slots' => $scheduler->daySlots($appointment->dentist, $appointment->duration_minutes, $date),
        ]);
    }

    public function updateSchedule(Request $request, Appointment $appointment, PredictiveScheduler $scheduler): RedirectResponse
    {
        $this->authorize('reschedule', $appointment);

        $request->validate(['scheduled_at' => ['required', 'date', 'after:now']]);
        $start = Carbon::parse($request->date('scheduled_at'));

        // Ignore this appointment itself when checking for clashes.
        if ($error = $this->slotProblem($scheduler, $appointment->dentist, $start, $appointment->duration_minutes, $appointment->id)) {
            return back()->withErrors(['scheduled_at' => $error]);
        }

        $appointment->update(['scheduled_at' => $start]);

        return redirect()->route('portal.appointments.index')
            ->with('status', 'Appointment moved to '.$start->format('M j, Y g:i A').'.');
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

    /**
     * Returns an error message if the slot is invalid, or null if it's bookable.
     * Shared clinic-hours + availability check (also used by the front desk).
     */
    private function slotProblem(PredictiveScheduler $scheduler, User $dentist, Carbon $start, int $duration, ?int $ignoreId = null): ?string
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
        if (! $scheduler->isSlotAvailable($dentist, $start, $duration, $ignoreId)) {
            return 'That slot is already taken for this dentist. Please pick another time.';
        }

        return null;
    }
}
