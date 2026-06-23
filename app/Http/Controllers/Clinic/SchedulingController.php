<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Services\ML\AppointmentFeatureExtractor;
use App\Services\ML\SchedulingModel;
use App\Services\PredictiveScheduler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchedulingController extends Controller
{
    /**
     * Predictive scheduling — suggest the next free slots, ranked by the Decision
     * Tree's predicted attendance ("keep") likelihood so the best times surface.
     */
    public function index(Request $request, PredictiveScheduler $scheduler, SchedulingModel $model, AppointmentFeatureExtractor $extractor): View
    {
        abort_unless(in_array($request->user()->role, [UserRole::Receptionist, UserRole::Management], true), 403);

        $suggestions = collect();
        $dentist = $request->filled('dentist_id') ? User::find($request->integer('dentist_id')) : null;
        $service = $request->filled('service_id') ? Service::find($request->integer('service_id')) : null;
        $fromDate = $request->filled('date') ? Carbon::parse($request->date('date')) : now();

        if ($dentist && $service) {
            $slots = $scheduler->suggestSlots($dentist, $service->duration_minutes, $fromDate, 9);

            $suggestions = $slots->map(fn (Carbon $slot) => [
                'time' => $slot,
                'keep' => $model->keepProbability(
                    $extractor->slotVector(null, $slot, $service->duration_minutes, (float) $service->price)
                ),
            ]);

            // Flag the slot(s) with the highest predicted attendance as "recommended".
            $best = $suggestions->whereNotNull('keep')->max('keep');
            $suggestions = $suggestions->map(fn ($s) => $s + [
                'recommended' => $s['keep'] !== null && $best !== null && $best > 0 && $s['keep'] >= $best - 0.0001,
            ]);
        }

        return view('clinic.scheduling.index', [
            'dentists' => User::where('role', UserRole::Dentist)->orderBy('name')->get(),
            'services' => Service::active()->orderBy('name')->get(),
            'suggestions' => $suggestions,
            'modelTrained' => $model->isTrained(),
            'selected' => [
                'dentist_id' => $dentist?->id,
                'service_id' => $service?->id,
                'date' => $fromDate->toDateString(),
            ],
        ]);
    }
}
