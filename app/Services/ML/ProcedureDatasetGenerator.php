<?php

namespace App\Services\ML;

/**
 * Generates a realistic synthetic training set for the procedure-recommendation
 * regression. Real intake→procedure history doesn't exist yet (intakes are new),
 * so we model plausible clinical relationships (with noise) and let logistic
 * regression LEARN them. As real data accrues, retrain on it instead.
 */
class ProcedureDatasetGenerator
{
    public function __construct(private IntakeFeatureExtractor $extractor) {}

    /**
     * @return array{samples: list<list<float>>, labels: array<string, list<string>>}
     */
    public function generate(int $n = 600): array
    {
        $samples = [];
        $labels = ['scaling' => [], 'filling' => [], 'root_canal' => [], 'extraction' => []];

        for ($k = 0; $k < $n; $k++) {
            $r = $this->randomIntake();
            $samples[] = $this->extractor->vector($r);

            foreach ($labels as $target => &$arr) {
                $arr[] = $this->bernoulli($this->probability($target, $r)) ? 'yes' : 'no';
            }
            unset($arr);
        }

        return ['samples' => $samples, 'labels' => $labels];
    }

    /** @return array<string, mixed> */
    private function randomIntake(): array
    {
        return [
            'toothache' => $this->chance(0.30), 'sensitivity' => $this->chance(0.40),
            'bleeding_gums' => $this->chance(0.35), 'bad_breath' => $this->chance(0.30),
            'swelling' => $this->chance(0.15), 'brushing_per_day' => random_int(0, 3),
            'flosses' => $this->chance(0.40), 'smoker' => $this->chance(0.25),
            'sugar_level' => ['low', 'medium', 'high'][random_int(0, 2)],
            'months_since_cleaning' => random_int(0, 24),
            'visible_plaque' => $this->chance(0.40), 'decay_observed' => $this->chance(0.35),
            'gum_condition' => ['healthy', 'gingivitis', 'periodontitis'][random_int(0, 2)],
            'missing_teeth' => random_int(0, 4), 'existing_fillings' => random_int(0, 6),
            'age' => random_int(8, 70),
        ];
    }

    /**
     * Probability a given procedure is indicated, via a logistic score over the
     * intake features (clinical heuristics).
     */
    private function probability(string $target, array $r): float
    {
        $gum = $this->extractor->gum($r['gum_condition']);
        $sugar = $this->extractor->sugar($r['sugar_level']);
        $b = fn ($v) => $v ? 1 : 0;

        $logit = match ($target) {
            'scaling' => -3.2 + 1.8 * $b($r['bleeding_gums']) + 1.6 * $b($r['visible_plaque'])
                + 0.08 * $r['months_since_cleaning'] + 0.8 * $b($r['bad_breath'])
                + 0.9 * $gum + 0.5 * $b($r['smoker']) - 0.8 * $b($r['flosses']),
            'filling' => -1.2 + 2.6 * $b($r['decay_observed']) + 1.0 * $b($r['sensitivity'])
                + 0.7 * $sugar - 0.4 * $b($r['flosses']),
            'root_canal' => -2.2 + 1.8 * $b($r['toothache']) + 1.6 * $b($r['decay_observed'])
                + 1.6 * $b($r['swelling']) + 0.8 * $b($r['sensitivity']),
            'extraction' => -2.4 + 2.0 * $b($r['swelling']) + 0.5 * $r['missing_teeth']
                + 1.2 * $gum + 1.0 * $b($r['decay_observed']) + 0.6 * $b($r['smoker']),
            default => -3.0,
        };

        return 1 / (1 + exp(-$logit));
    }

    private function bernoulli(float $p): bool
    {
        return (mt_rand() / mt_getrandmax()) < $p;
    }

    private function chance(float $p): bool
    {
        return (mt_rand() / mt_getrandmax()) < $p;
    }
}
