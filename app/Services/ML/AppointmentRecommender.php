<?php

namespace App\Services\ML;

use App\Enums\AdviceStatus;
use App\Enums\Priority;
use App\Enums\RecommendationSource;
use App\Models\Appointment;
use App\Models\AppointmentRecommendation;
use App\Models\Service;
use App\Services\PredictiveScheduler;
use Carbon\Carbon;

/**
 * Turns a single appointment's Stage-1 intake or Stage-2 clinical findings into a
 * regression-backed recommendation, mapped onto the existing intake feature vector
 * so the trained per-procedure models are reused as-is (no retraining required).
 *
 * Decision-support only: every result is stored as a "suggested" recommendation the
 * dentist still verifies, edits, accepts or rejects.
 */
class AppointmentRecommender
{
    public function __construct(
        private ProcedureRecommendationModel $model,
        private IntakeFeatureExtractor $extractor,
        private PredictiveScheduler $scheduler,
    ) {}

    /**
     * Stage 1 — possible current treatment from the patient's pre-appointment form.
     */
    public function generateStage1(Appointment $appointment): AppointmentRecommendation
    {
        $appointment->loadMissing(['intake', 'patient.intake']);
        $features = $this->stage1Vector($appointment);
        $top = $this->topProcedure($features);

        return $this->persist($appointment, RecommendationSource::Stage1Current, $top, null);
    }

    /**
     * Stage 2 — recommended next visit from the dentist's clinical findings.
     */
    public function generateStage2(Appointment $appointment): AppointmentRecommendation
    {
        $appointment->loadMissing(['finding', 'intake', 'patient.intake']);
        $features = $this->stage2Vector($appointment);
        $top = $this->topProcedure($features);

        $weeks = $this->followUpWeeks($top['priority']);

        return $this->persist($appointment, RecommendationSource::Stage2Next, $top, $weeks);
    }

    /**
     * Use the Decision-Tree scheduler to propose a follow-up date/time for an accepted
     * next-visit recommendation, "follow_up_weeks" out, on the same dentist.
     */
    public function suggestFollowUp(AppointmentRecommendation $rec): ?Carbon
    {
        $appointment = $rec->appointment;
        $dentist = $appointment->dentist;
        if (! $dentist) {
            return null;
        }

        $weeks = $rec->follow_up_weeks ?: 4;
        $from = now()->addWeeks($weeks);
        $duration = $rec->service?->duration_minutes ?? 30;

        return $this->scheduler->suggestSlots($dentist, $duration, $from, 1)->first();
    }

    /**
     * Persist (or refresh the still-"suggested" row) for this stage.
     */
    private function persist(Appointment $appointment, RecommendationSource $source, array $top, ?int $weeks): AppointmentRecommendation
    {
        // Replace only a prior un-acted suggestion; never overwrite an accepted/rejected one.
        $existing = $appointment->recommendations()
            ->where('source', $source->value)
            ->where('status', AdviceStatus::Suggested->value)
            ->first();

        $payload = [
            'source' => $source->value,
            'recommendation' => $top['text'],
            'linked_service_id' => $top['service']?->id,
            'confidence' => $top['score'],
            'priority' => $top['priority']->value,
            'follow_up_weeks' => $weeks,
            'status' => AdviceStatus::Suggested->value,
        ];

        if ($existing) {
            $existing->update($payload);

            return $existing;
        }

        return $appointment->recommendations()->create($payload);
    }

    /**
     * Highest-scoring procedure for a feature vector → recommendation text + meta.
     *
     * @return array{text:string, service:?Service, score:?float, priority:Priority}
     */
    private function topProcedure(array $features): array
    {
        if (! $this->model->isTrained()) {
            return [
                'text' => 'Dental examination and evaluation (model not yet trained — clinical review advised).',
                'service' => null,
                'score' => null,
                'priority' => Priority::Medium,
            ];
        }

        $ranked = $this->model->score($features);
        $best = $ranked->first();

        if (! $best || $best->score < 0.35) {
            return [
                'text' => 'Routine dental examination and cleaning.',
                'service' => Service::where('name', 'Dental Cleaning')->first(),
                'score' => $best?->score,
                'priority' => Priority::Low,
            ];
        }

        return [
            'text' => $best->label,
            'service' => Service::where('name', $best->service)->first(),
            'score' => $best->score,
            'priority' => $this->priorityFromScore($best->score),
        ];
    }

    private function priorityFromScore(float $score): Priority
    {
        return match (true) {
            $score >= 0.66 => Priority::High,
            $score >= 0.45 => Priority::Medium,
            default => Priority::Low,
        };
    }

    private function followUpWeeks(Priority $priority): int
    {
        return match ($priority) {
            Priority::High => 2,
            Priority::Medium => 6,
            Priority::Low => 12,
        };
    }

    /**
     * Stage-1 vector: patient-reported symptoms/behaviours, with clinician-observed
     * fields filled from the patient's standing clinical record (or sensible defaults).
     */
    private function stage1Vector(Appointment $appointment): array
    {
        $intake = $appointment->intake;
        $clinical = $appointment->patient?->intake; // standing per-patient record, if any

        return $this->extractor->vector([
            'toothache' => $intake?->toothache ?? false,
            'sensitivity' => $intake?->sensitivity ?? false,
            'bleeding_gums' => $intake?->bleeding_gums ?? false,
            'bad_breath' => $intake?->bad_breath ?? false,
            'swelling' => $intake?->swelling ?? false,
            'brushing_per_day' => $intake?->brushing_per_day ?? 2,
            'flosses' => $intake?->flosses ?? false,
            'smoker' => $intake?->smoker ?? false,
            'sugar_level' => $intake?->sugar_level ?? 'medium',
            'months_since_cleaning' => $intake?->months_since_cleaning ?? 6,
            'visible_plaque' => $clinical?->visible_plaque ?? false,
            'decay_observed' => $clinical?->decay_observed ?? false,
            'gum_condition' => $clinical?->gum_condition ?? 'healthy',
            'missing_teeth' => $clinical?->missing_teeth ?? 0,
            'existing_fillings' => $clinical?->existing_fillings ?? 0,
            'age' => $this->age($appointment),
        ]);
    }

    /**
     * Stage-2 vector: the same patient inputs, refined by the dentist's actual findings.
     */
    private function stage2Vector(Appointment $appointment): array
    {
        $intake = $appointment->intake;
        $finding = $appointment->finding;
        $clinical = $appointment->patient?->intake;

        return $this->extractor->vector([
            'toothache' => $intake?->toothache ?? false,
            'sensitivity' => $intake?->sensitivity ?? false,
            'bleeding_gums' => $intake?->bleeding_gums ?? ($finding && $finding->gum_inflammation !== 'none'),
            'bad_breath' => $intake?->bad_breath ?? false,
            'swelling' => $intake?->swelling ?? ($finding?->infection_signs === 'present'),
            'brushing_per_day' => $intake?->brushing_per_day ?? 2,
            'flosses' => $intake?->flosses ?? false,
            'smoker' => $intake?->smoker ?? false,
            'sugar_level' => $intake?->sugar_level ?? 'medium',
            'months_since_cleaning' => $intake?->months_since_cleaning ?? 6,
            'visible_plaque' => in_array($finding?->plaque_level, ['medium', 'high'], true),
            'decay_observed' => (bool) $finding?->cavity_found,
            'gum_condition' => $this->mapGum($finding?->gum_inflammation),
            'missing_teeth' => $finding?->missing_teeth ?? $clinical?->missing_teeth ?? 0,
            'existing_fillings' => $finding?->existing_fillings ?? $clinical?->existing_fillings ?? 0,
            'age' => $this->age($appointment),
        ]);
    }

    private function mapGum(?string $inflammation): string
    {
        return match ($inflammation) {
            'severe' => 'periodontitis',
            'moderate', 'mild' => 'gingivitis',
            default => 'healthy',
        };
    }

    private function age(Appointment $appointment): int
    {
        $dob = $appointment->patient?->date_of_birth;

        return $dob ? max(1, (int) $dob->diffInYears(now())) : 30;
    }
}
