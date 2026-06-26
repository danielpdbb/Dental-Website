<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AppointmentStatus;
use App\Enums\RecommendationSource;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\BookAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentRecommendation;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\PredictiveScheduler;
use App\Services\RewardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request, RewardService $rewards): View
    {
        $patient = RecordController::resolvePatient($request->user());

        $pastStatus = $request->string('past_status')->toString();
        $pastStatus = in_array($pastStatus, ['completed', 'cancelled', 'no_show'], true) ? $pastStatus : null;

        return view('portal.appointments.index', [
            // Active = anything not yet finished (booked → billed) so a Billed visit
            // always shows here with its pay options; History = terminal states.
            // Active = not finished AND (still in the future OR already in the billing
            // pipeline). A past-dated visit that's still only "booked" was never
            // processed, so it drops to Past instead of lingering as "upcoming".
            'upcoming' => $patient->appointments()
                ->whereNotIn('status', ['completed', 'cancelled', 'no_show'])
                ->where(function ($q) {
                    $q->where('scheduled_at', '>=', now()->startOfDay())
                        ->orWhereIn('status', ['in_treatment', 'for_billing', 'billed']);
                })
                ->with(['service', 'dentist', 'payments', 'procedures', 'intake', 'recommendations.service', 'billingStatement.items'])
                ->orderBy('scheduled_at')->get(),
            'past' => $patient->appointments()
                ->where(function ($q) {
                    $q->whereIn('status', ['completed', 'cancelled', 'no_show'])
                        ->orWhere(fn ($q2) => $q2->where('status', 'booked')->where('scheduled_at', '<', now()->startOfDay()));
                })
                ->when($pastStatus, fn ($q) => $q->where('status', $pastStatus))
                ->with(['service', 'dentist', 'payments', 'procedures', 'billingStatement.items'])->latest('scheduled_at')
                ->paginate(8, ['*'], 'past')->withQueryString(),
            'pastStatus' => $pastStatus,
            'outstanding' => $patient->appointments()->where('status', AppointmentStatus::Billed->value)->with('payments')->get()
                ->sum(fn ($a) => $a->balance()),
            // Rewards context for the "apply credit" option on each bill.
            'rewardPeso' => $rewards->pesoBalance($request->user()),
            'minRedeemPeso' => $rewards->pesoValue((int) config('rewards.min_redeem_points')),
            // Dentist-sent next-visit recommendations (with one-tap "Book this").
            'recommendations' => $this->sentRecommendations($patient),
        ]);
    }

    /**
     * Accepted + sent next-visit recommendations for this patient, newest first.
     */
    private function sentRecommendations(Patient $patient)
    {
        return AppointmentRecommendation::sentUpcoming()
            ->whereHas('appointment', fn ($q) => $q->where('patient_id', $patient->id))
            ->with(['appointment.dentist', 'service'])
            ->take(8)
            ->get();
    }

    /**
     * Printable invoice for the patient's own fully-paid appointment.
     */
    public function invoice(Request $request, Appointment $appointment)
    {
        $patient = RecordController::resolvePatient($request->user());
        abort_unless($appointment->patient_id === $patient->id, 403);

        $statement = $appointment->billingStatement;
        abort_if(! $statement || ! $statement->invoice_no, 404, 'An invoice is available once your bill is fully paid.');

        $appointment->load(['patient', 'dentist', 'payments']);
        $statement->load('items');

        return Pdf::loadView('clinic.billing.document', [
            'appointment' => $appointment,
            'statement' => $statement,
            'type' => 'invoice',
        ])->stream('invoice-'.$appointment->id.'.pdf');
    }

    public function create(Request $request, PredictiveScheduler $scheduler, \App\Services\ML\SchedulingModel $model, \App\Services\ML\AppointmentFeatureExtractor $extractor): View
    {
        $selectedIds = collect($request->input('service_ids', []))
            ->filter()->map(fn ($v) => (int) $v)->values();

        $selected = $selectedIds->isNotEmpty()
            ? Service::active()->whereIn('id', $selectedIds)->orderBy('name')->get()
            : collect();

        $dentists = User::where('role', UserRole::Dentist)->orderBy('name')->get();
        $dentist = $request->filled('dentist_id') ? $dentists->firstWhere('id', $request->integer('dentist_id')) : null;
        $date = $request->filled('date') ? Carbon::parse($request->date('date')) : Carbon::today();

        $totalDuration = (int) $selected->sum('duration_minutes');
        $totalPrice = (float) $selected->sum('price');

        // Only build the time grid once at least one service + a dentist are chosen.
        $slots = ($selected->isNotEmpty() && $dentist)
            ? $scheduler->daySlots($dentist, max(15, $totalDuration), $date)
            : null;

        // Decision-Tree recommended slot: best predicted-attendance free slot across
        // dentists. Verified available before it is suggested.
        $recommended = $selected->isNotEmpty()
            ? $this->recommendSlot($scheduler, $model, $extractor, $dentists, max(15, $totalDuration), $totalPrice)
            : null;

        return view('portal.appointments.create', [
            'services' => Service::active()->orderBy('name')->get(),
            'dentists' => $dentists,
            'selected' => $selected,
            'selectedIds' => $selectedIds->all(),
            'dentist' => $dentist,
            'date' => $date,
            'slots' => $slots,
            'totalDuration' => $totalDuration,
            'totalPrice' => $totalPrice,
            'recommended' => $recommended,
            // Dentist-sent next-visit recommendations (with one-tap "Book this").
            'recommendations' => $this->sentRecommendations(RecordController::resolvePatient($request->user())),
        ]);
    }

    /**
     * Pick the free slot with the highest predicted attendance across all dentists.
     *
     * @return array{dentist: User, time: Carbon, keep: ?float}|null
     */
    private function recommendSlot(PredictiveScheduler $scheduler, \App\Services\ML\SchedulingModel $model, \App\Services\ML\AppointmentFeatureExtractor $extractor, $dentists, int $duration, float $price): ?array
    {
        $best = null;

        foreach ($dentists as $dentist) {
            foreach ($scheduler->suggestSlots($dentist, $duration, now(), 3) as $slot) {
                // Only suggest genuinely free slots.
                if (! $scheduler->isSlotAvailable($dentist, $slot, $duration)) {
                    continue;
                }
                $keep = $model->keepProbability($extractor->slotVector(null, $slot, $duration, $price));
                $score = $keep ?? 0.0;

                if (! $best || $score > $best['score'] || ($score === $best['score'] && $slot->lt($best['time']))) {
                    $best = ['dentist' => $dentist, 'time' => $slot, 'keep' => $keep, 'score' => $score];
                }
            }
        }

        return $best ? ['dentist' => $best['dentist'], 'time' => $best['time'], 'keep' => $best['keep']] : null;
    }

    public function store(BookAppointmentRequest $request, PredictiveScheduler $scheduler): RedirectResponse
    {
        $patient = RecordController::resolvePatient($request->user());
        $services = Service::active()->whereIn('id', $request->validated('service_ids'))->get();
        $dentist = User::findOrFail($request->integer('dentist_id'));
        $start = Carbon::parse($request->date('scheduled_at'));
        $duration = max(15, (int) $services->sum('duration_minutes'));

        if ($error = $this->slotProblem($scheduler, $dentist, $start, $duration)) {
            return back()->withInput()->withErrors(['scheduled_at' => $error]);
        }

        $appointment = $patient->appointments()->create([
            'dentist_id' => $dentist->id,
            'service_id' => $services->first()->id, // primary service (back-compat)
            'scheduled_at' => $start,
            'duration_minutes' => $duration,
            'total_amount' => round((float) $services->sum('price'), 2),
            'status' => AppointmentStatus::Booked,
            'notes' => $request->input('notes'),
            'created_by' => $request->user()->id,
        ]);

        $this->attachProcedures($appointment, $services);

        $request->user()->notify(new \App\Notifications\PatientAlert(
            'Appointment confirmed',
            'Your appointment with '.$dentist->name.' is booked for '.$start->format('l, M j · g:i A').'.',
            route('portal.appointments.index'),
            email: true,
        ));

        // Alert the front desk that a patient booked online.
        \App\Support\Notifier::desk(
            'New online booking',
            $patient->fullName().' booked '.$services->pluck('name')->join(', ').' with '.$dentist->name.' on '.$start->format('M j · g:i A').'.',
            route('clinic.appointments.show', $appointment),
        );

        return redirect()->route('portal.appointments.index')
            ->with('status', 'Appointment booked for '.$start->format('M j, Y g:i A').'.');
    }

    /**
     * Create a procedure line item per chosen service.
     */
    private function attachProcedures(Appointment $appointment, $services): void
    {
        foreach ($services as $service) {
            $appointment->procedures()->create([
                'service_id' => $service->id,
                'procedure_name' => $service->name,
                'price' => $service->price,
                'duration_minutes' => $service->duration_minutes,
                'status' => \App\Enums\ProcedureStatus::Planned,
            ]);
        }
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

        $from = $appointment->scheduled_at->copy();
        $appointment->update(['scheduled_at' => $start]);

        \App\Support\Notifier::desk(
            'Appointment rescheduled by patient',
            ($appointment->patient?->fullName() ?? 'A patient').' moved their visit from '.$from->format('M j · g:i A').' to '.$start->format('M j · g:i A').'.',
            route('clinic.appointments.show', $appointment),
        );

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

        \App\Support\Notifier::desk(
            'Appointment cancelled by patient',
            ($appointment->patient?->fullName() ?? 'A patient').' cancelled their '.$appointment->scheduled_at->format('M j · g:i A').' visit'.($request->filled('reason') ? ' — “'.$request->input('reason').'”.' : '.'),
            route('clinic.appointments.show', $appointment),
        );

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
