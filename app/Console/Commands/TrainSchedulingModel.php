<?php

namespace App\Console\Commands;

use App\Services\ML\AppointmentFeatureExtractor;
use App\Services\ML\SchedulingModel;
use Illuminate\Console\Command;
use Rubix\ML\Classifiers\ClassificationTree;
use Rubix\ML\CrossValidation\Metrics\Accuracy;
use Rubix\ML\CrossValidation\Metrics\FBeta;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\PersistentModel;

/**
 * Trains the predictive-scheduling Decision Tree (attendance: kept vs missed) from
 * historical appointments and saves it to storage/app/models/scheduling.model.
 *
 * Run: php artisan ml:scheduling:train
 */
class TrainSchedulingModel extends Command
{
    protected $signature = 'ml:scheduling:train {--depth=6 : Max tree height}';

    protected $description = 'Train the appointment-attendance Decision Tree used for predictive scheduling';

    public function handle(AppointmentFeatureExtractor $extractor): int
    {
        [$samples, $labels] = $extractor->trainingData();
        $n = count($samples);

        if ($n < 10) {
            $this->error("Only {$n} labelled appointments — not enough to train. Seed/collect more history first.");

            return self::FAILURE;
        }
        if ($n < 40) {
            $this->warn("Only {$n} labelled appointments — the model will be weak. More history is better.");
        }

        $kept = count(array_filter($labels, fn ($l) => $l === 'kept'));
        $this->line("Dataset: {$n} appointments — {$kept} kept / ".($n - $kept).' missed.');

        $dataset = new Labeled($samples, $labels);
        [$training, $testing] = $dataset->stratifiedSplit(0.8);

        @mkdir(dirname(SchedulingModel::path()), 0775, true);

        $estimator = new PersistentModel(
            new ClassificationTree((int) $this->option('depth')),
            new Filesystem(SchedulingModel::path(), true),
        );

        $this->info('Training Decision Tree on '.$training->numSamples().' samples…');
        $estimator->train($training);

        $predictions = $estimator->predict($testing);
        $accuracy = (new Accuracy)->score($predictions, $testing->labels());
        $f1 = (new FBeta)->score($predictions, $testing->labels());

        $estimator->save();

        $this->newLine();
        $this->info(sprintf('✓ Trained. Test accuracy %.1f%% · F1 %.2f', $accuracy * 100, $f1));
        $this->line('Saved to '.SchedulingModel::path());

        return self::SUCCESS;
    }
}
