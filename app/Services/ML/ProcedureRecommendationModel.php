<?php

namespace App\Services\ML;

use Illuminate\Support\Collection;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

/**
 * Loads the per-procedure logistic-regression models and scores how strongly a
 * patient's intake indicates each procedure. Returns null/empty until trained.
 */
class ProcedureRecommendationModel
{
    /** target => [display label, linked service name] */
    public const TARGETS = [
        'scaling' => ['label' => 'Scaling / Cleaning', 'service' => 'Dental Cleaning'],
        'filling' => ['label' => 'Composite Filling', 'service' => 'Composite Filling'],
        'root_canal' => ['label' => 'Root Canal Treatment', 'service' => 'Root Canal Treatment'],
        'extraction' => ['label' => 'Tooth Extraction', 'service' => 'Tooth Extraction'],
    ];

    /** @var array<string, object|null> */
    private static array $cache = [];

    public static function path(string $target): string
    {
        return storage_path("app/models/recommend_{$target}.model");
    }

    public function isTrained(): bool
    {
        return is_file(self::path('scaling'));
    }

    private function model(string $target): ?object
    {
        if (! array_key_exists($target, self::$cache)) {
            self::$cache[$target] = null;
            if (is_file(self::path($target))) {
                try {
                    self::$cache[$target] = PersistentModel::load(new Filesystem(self::path($target)));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return self::$cache[$target];
    }

    /**
     * Ranked procedure suggestions for a feature vector.
     *
     * @return Collection<int, object{key:string,label:string,service:string,score:float}>
     */
    public function score(array $features): Collection
    {
        $out = collect();

        foreach (self::TARGETS as $target => $meta) {
            $model = $this->model($target);
            if (! $model) {
                continue;
            }

            try {
                $proba = $model->proba(new Unlabeled([$features]));
                $p = (float) ($proba[0]['yes'] ?? 0.0);
            } catch (\Throwable $e) {
                report($e);

                continue;
            }

            $out->push((object) [
                'key' => $target,
                'label' => $meta['label'],
                'service' => $meta['service'],
                'score' => $p,
            ]);
        }

        return $out->sortByDesc('score')->values();
    }
}
