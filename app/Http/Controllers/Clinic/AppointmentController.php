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
    /** Status groups behind the index tabs. */
    private const TABS = [
        'active' => ['booked', 'in_treatment', 'for_billing'],
        'billed' => ['billed'],
        'finished' => ['completed', 'no_show', 'cancelled'],
    ];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Appointment::class);

        $tab = $request->string('tab')->toString();
        $tab = array_key_exists($tab, self::TABS) ? $tab : 'active';

        // Live patient search — server-side LIKE so it scales past thousands of records.
        $q = $request->string('q')->trim()->value();

        // Optional status filter — only statuses that belong to the active tab.
        $status = $request->string('status')->toString();
        $status = in_array($status, self::TABS[$tab], true) ? $status : null;

        $appointments = Appointment::query()
            ->with(['patient', 'dentist', 'service', 'payments', 'procedures'])
            ->whereIn('status', $status ? [$status] : self::TABS[$tab])
            ->when($request->filled('dentist_id'), fn ($qr) => $qr->where('dentist_id', $request->integer('dentist_id')))
            ->when($request->filled('date'), fn ($qr) => $qr->whereDate('scheduled_at', $request->date('date')))
            ->when($q, fn ($qr) => $qr->whereHas('patient', fn ($p) => $p
                ->where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhereRaw("concat(first_name, ' ', last_name) like ?", ["%{$q}%"])
                ->orWhere('phone', 'like', "%{$q}%")))
            ->orderByDesc('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        // Tab counts (respect the same search/dentist/date filters so they stay honest).
        $countBase = fn (array $statuses) => Appointment::query()
            ->whereIn('status', $statuses)
            ->when($request->filled('dentist_id'), fn ($qr) => $qr->where('dentist_id', $request->integer('dentist_id')))
            ->when($request->filled('date'), fn ($qr) => $qr->whereDate('scheduled_at', $request->date('date')))
            ->when($q, fn ($qr) => $qr->whereHas('patient', fn ($p) => $p
                ->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")
                ->orWhereRaw("concat(first_name, ' ', last_name) like ?", ["%{$q}%"])->orWhere('phone', 'like', "%{$q}%")))
            ->count();

        return view('clinic.appointments.index', [
            'appointments' => $appointments,
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'tab' => $tab,
            'q' => $q,
            'status' => $status,
            'tabStatuses' => self::TABS[$tab],
            'counts' => [
                'active' => $countBase(self::TABS['active']),
                'billed' => $countBase(self::TABS['billed']),
                'finished' => $countBase(self::TABS['finished']),
            ],
            'filters' => $request->only('dentist_id', 'date'),
        ]);
    }

    public function create(Request $request, PredictiveScheduler $scheduler): View
    {
        $this->authorize('create', Appointment::class);

        $services = Service::active()->orderBy('name')->get();
        $dentists = User::where('role', UserRole::Dentist)->orderBy('name')->get();

        // Selected services (supports the old ?service_id= prefill from "Find slots").
        $selectedIds = collect((array) $request->input('service_ids', $request->filled('service_id') ? [$request->integer('service_id')] : []))
            ->map(fn ($v) => (int) $v)->filter()->values()->all();
        $selected = $services->whereIn('id', $selectedIds);
        $duration = max(15, (int) $selected->sum('duration_minutes'));

        $dentist = $request->filled('dentist_id') ? $dentists->firstWhere('id', $request->integer('dentist_id')) : null;

        // Selected existing patient — autofills + locks the name/phone fields.
        $selectedPatient = $request->filled('patient_id') ? Patient::find($request->integer('patient_id')) : null;

        // Every htmx swap (service/dentist/date change) re-renders this WHOLE form, so
        // read walk-in + the new-patient fields from the request (not old(), which is
        // only populated after a failed POST redirect) to keep them from resetting.
        $isWalkIn = $request->boolean('is_walk_in');
        $newFirstName = $request->string('new_first_name')->toString();
        $newLastName = $request->string('new_last_name')->toString();
        $newPhone = $request->string('new_phone')->toString();

        // Calendar month + explicitly picked date (today → +3 months). Walk-ins default
        // to today so the desk immediately sees what's still free.
        $date = $request->filled('date') ? Carbon::parse($request->date('date')) : Carbon::today();
        if ($date->isBefore(today()) || $date->gt(now()->addMonths(PredictiveScheduler::MAX_MONTHS_AHEAD))) {
            $date = Carbon::today();
        }
        $calMonth = $request->filled('cal') ? Carbon::parse($request->string('cal').'-01') : $date->copy();
        $calMonth = $calMonth->startOfMonth();
        $calMonth = max($calMonth, now()->startOfMonth());
        $calMonth = min($calMonth, now()->copy()->addMonths(PredictiveScheduler::MAX_MONTHS_AHEAD)->startOfMonth());

        $ready = $dentist && $selected->isNotEmpty();
        $monthDays = $ready ? $scheduler->monthOverview($dentist, $calMonth, $duration) : null;
        $slots = $ready ? $scheduler->daySlots($dentist, $duration, $date) : null;

        // Next free slot (today onwards) — handy when today is fully booked and the
        // desk needs a date to recommend to a walk-in.
        $nextFree = $ready ? $scheduler->suggestSlots($dentist, $duration, now(), 1)->first() : null;

        // Follow-up targets for the chosen patient (charges consolidate on one bill).
        $followTargets = collect();
        if ($request->filled('patient_id')) {
            $followTargets = Appointment::where('patient_id', $request->integer('patient_id'))
                ->whereIn('status', [AppointmentStatus::Billed->value, AppointmentStatus::Completed->value])
                ->where('scheduled_at', '>=', now()->subMonths(6))
                ->with(['procedures', 'payments'])
                ->latest('scheduled_at')->take(10)->get();
        }

        return view('clinic.appointments.create', [
            'patients' => Patient::orderBy('last_name')->get(),
            'services' => $services,
            'dentists' => $dentists,
            'selectedIds' => $selectedIds,
            'selected' => $selected,
            'duration' => $duration,
            'dentist' => $dentist,
            'date' => $date,
            'calMonth' => $calMonth,
            'monthDays' => $monthDays,
            'slots' => $slots,
            'nextFree' => $nextFree,
            'followTargets' => $followTargets,
            'selectedPatient' => $selectedPatient,
            'isWalkIn' => $isWalkIn,
            'newFirstName' => $newFirstName,
            'newLastName' => $newLastName,
            'newPhone' => $newPhone,
            'prefill' => $request->only('patient_id'),
        ]);
    }

    public function store(StoreClinicAppointmentRequest $request, PredictiveScheduler $scheduler): RedirectResponse
    {
        $services = Service::active()->whereIn('id', $request->validated('service_ids'))->get();
        $dentist = User::findOrFail($request->integer('dentist_id'));
        $isWalkIn = $request->boolean('is_walk_in');
        // Everyone (walk-ins included) books into a real free slot to avoid conflicts.
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

        // Optional follow-up link — must belong to the same patient.
        $parentId = $request->integer('parent_appointment_id') ?: null;
        if ($parentId && ! Appointment::where('patient_id', $patient->id)->whereKey($parentId)->exists()) {
            $parentId = null;
        }

        $appointment = $patient->appointments()->create([
            'dentist_id' => $dentist->id,
            'service_id' => $services->first()->id, // primary service (back-compat)
            'parent_appointment_id' => $parentId,
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

        $appointment->load(['patient.user', 'dentist', 'service', 'payments.recorder', 'creator', 'canceller', 'procedures.service', 'procedures.performer', 'billingStatement.items', 'recommendations.service', 'parent.billingStatement', 'followUps']);

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

    /**
     * Calendar + time-slot picker for changing an appointment's date/time — same UX as
     * booking, loaded lazily (htmx) into the appointment page.
     */
    public function rescheduleForm(Request $request, Appointment $appointment, PredictiveScheduler $scheduler): View
    {
        $this->authorize('reschedule', $appointment);
        $appointment->load('dentist');

        $date = $request->filled('date') ? Carbon::parse($request->date('date')) : $appointment->scheduled_at->copy()->max(Carbon::today());
        if ($date->isBefore(today()) || $date->gt(now()->addMonths(PredictiveScheduler::MAX_MONTHS_AHEAD))) {
            $date = Carbon::today();
        }
        $calMonth = $request->filled('cal') ? Carbon::parse($request->string('cal').'-01') : $date->copy();
        $calMonth = $calMonth->startOfMonth();
        $calMonth = max($calMonth, now()->startOfMonth());
        $calMonth = min($calMonth, now()->copy()->addMonths(PredictiveScheduler::MAX_MONTHS_AHEAD)->startOfMonth());

        $duration = max(15, (int) $appointment->duration_minutes);

        return view('clinic.appointments._reschedule', [
            'appointment' => $appointment,
            'duration' => $duration,
            'date' => $date,
            'calMonth' => $calMonth,
            'monthDays' => $scheduler->monthOverview($appointment->dentist, $calMonth, $duration, $appointment->id),
            'slots' => $scheduler->daySlots($appointment->dentist, $duration, $date, $appointment->id),
        ]);
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
        // Small grace period so a walk-in slot picked moments ago still validates.
        if ($start->lt(now()->subMinutes(15))) {
            return 'Please choose a future date and time.';
        }
        if ($start->gt(now()->addMonths(PredictiveScheduler::MAX_MONTHS_AHEAD))) {
            return 'Bookings can be made up to 3 months in advance.';
        }

        // Dentist-specific hours (weekly rules + date blocks) are enforced by
        // PredictiveScheduler::isSlotAvailable in store().
        return null;
    }
}
