<?php

namespace App\Services\ML;

use App\Models\ClinicalIntake;

/**
 * Converts a clinical intake (symptoms / behaviors / indicators) into the numeric
 * feature vector the procedure-recommendation regression models use. The same
 * vector() is used for training samples and live predictions so columns line up.
 */
class IntakeFeatureExtractor
{
    public const FEATURES = [
        'toothache', 'sensitivity', 'bleeding_gums', 'bad_breath', 'swelling',
        'brushing_per_day', 'flosses', 'smoker', 'sugar_level', 'months_since_cleaning',
        'visible_plaque', 'decay_observed', 'gum_condition', 'missing_teeth', 'existing_fillings', 'age',
    ];

    public function fromIntake(ClinicalIntake $i, int $age): array
    {
        return $this->vector([
            'toothache' => $i->toothache, 'sensitivity' => $i->sensitivity, 'bleeding_gums' => $i->bleeding_gums,
            'bad_breath' => $i->bad_breath, 'swelling' => $i->swelling,
            'brushing_per_day' => $i->brushing_per_day, 'flosses' => $i->flosses, 'smoker' => $i->smoker,
            'sugar_level' => $i->sugar_level, 'months_since_cleaning' => $i->months_since_cleaning,
            'visible_plaque' => $i->visible_plaque, 'decay_observed' => $i->decay_observed,
            'gum_condition' => $i->gum_condition, 'missing_teeth' => $i->missing_teeth,
            'existing_fillings' => $i->existing_fillings, 'age' => $age,
        ]);
    }

    /**
     * @param  array<string, mixed>  $r
     * @return list<float>
     */
    public function vector(array $r): array
    {
        return [
            $this->b($r['toothache'] ?? false),
            $this->b($r['sensitivity'] ?? false),
            $this->b($r['bleeding_gums'] ?? false),
            $this->b($r['bad_breath'] ?? false),
            $this->b($r['swelling'] ?? false),
            (float) ($r['brushing_per_day'] ?? 2),
            $this->b($r['flosses'] ?? false),
            $this->b($r['smoker'] ?? false),
            (float) $this->sugar($r['sugar_level'] ?? 'medium'),
            (float) ($r['months_since_cleaning'] ?? 6),
            $this->b($r['visible_plaque'] ?? false),
            $this->b($r['decay_observed'] ?? false),
            (float) $this->gum($r['gum_condition'] ?? 'healthy'),
            (float) ($r['missing_teeth'] ?? 0),
            (float) ($r['existing_fillings'] ?? 0),
            (float) ($r['age'] ?? 30),
        ];
    }

    private function b(mixed $v): float
    {
        return $v ? 1.0 : 0.0;
    }

    public function sugar(string $v): int
    {
        return ['low' => 0, 'medium' => 1, 'high' => 2][$v] ?? 1;
    }

    public function gum(string $v): int
    {
        return ['healthy' => 0, 'gingivitis' => 1, 'periodontitis' => 2][$v] ?? 0;
    }
}
