<?php

namespace App\Services\ML;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

/**
 * Loads the trained scheduling Decision Tree and answers "how likely is this
 * appointment/slot to be KEPT?". Returns null (→ caller falls back to rules) when
 * no model has been trained yet.
 */
class SchedulingModel
{
    private static ?object $estimator = null;
    private static bool $loaded = false;

    public static function path(): string
    {
        return storage_path('app/models/scheduling.model');
    }

    public function isTrained(): bool
    {
        return is_file(self::path());
    }

    private function estimator(): ?object
    {
        if (! self::$loaded) {
            self::$loaded = true;
            if (is_file(self::path())) {
                try {
                    self::$estimator = PersistentModel::load(new Filesystem(self::path()));
                } catch (\Throwable $e) {
                    report($e);
                    self::$estimator = null;
                }
            }
        }

        return self::$estimator;
    }

    /**
     * Probability (0..1) that an appointment with these features is KEPT, or null.
     */
    public function keepProbability(array $features): ?float
    {
        $estimator = $this->estimator();
        if (! $estimator) {
            return null;
        }

        try {
            $proba = $estimator->proba(new Unlabeled([$features]));

            return (float) ($proba[0]['kept'] ?? 0.0);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Map a keep-probability to a no-show risk label + Tailwind badge classes.
     *
     * @return array{0:string,1:string}
     */
    public function riskBadge(float $keepProbability): array
    {
        $missed = 1 - $keepProbability;

        return match (true) {
            $missed >= 0.5 => ['High', 'bg-red-100 text-red-600'],
            $missed >= 0.25 => ['Medium', 'bg-amber-100 text-amber-700'],
            default => ['Low', 'bg-brand-green/10 text-emerald-700'],
        };
    }
}
