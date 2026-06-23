<?php

namespace App\Console\Commands;

use App\Services\ML\ProcedureDatasetGenerator;
use App\Services\ML\ProcedureRecommendationModel;
use Illuminate\Console\Command;
use Rubix\ML\Classifiers\LogisticRegression;
use Rubix\ML\CrossValidation\Metrics\Accuracy;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\PersistentModel;
use Rubix\ML\Pipeline;
use Rubix\ML\Transformers\ZScaleStandardizer;

/**
 * Trains one logistic-regression model per procedure (the Regression Analysis for
 * procedure recommendation) on synthetic intake data. Saves to
 * storage/app/models/recommend_{target}.model.
 *
 * Run: php artisan ml:recommend:train
 */
class TrainRecommendationModel extends Command
{
    protected $signature = 'ml:recommend:train {--samples=600 : Synthetic samples to generate}';

    protected $description = 'Train the procedure-recommendation regression models (one per procedure)';

    public function handle(ProcedureDatasetGenerator $generator): int
    {
        $samples = (int) $this->option('samples');
        $data = $generator->generate($samples);

        @mkdir(dirname(ProcedureRecommendationModel::path('scaling')), 0775, true);

        $this->line("Training on {$samples} synthetic intake samples…");
        $this->newLine();

        foreach (ProcedureRecommendationModel::TARGETS as $target => $meta) {
            $dataset = new Labeled($data['samples'], $data['labels'][$target]);
            [$training, $testing] = $dataset->stratifiedSplit(0.8);

            $estimator = new PersistentModel(
                new Pipeline([new ZScaleStandardizer], new LogisticRegression),
                new Filesystem(ProcedureRecommendationModel::path($target), true),
            );

            $estimator->train($training);
            $accuracy = (new Accuracy)->score($estimator->predict($testing), $testing->labels());
            $estimator->save();

            $yes = count(array_filter($data['labels'][$target], fn ($l) => $l === 'yes'));
            $this->info(sprintf('  %-22s test acc %.1f%%  (%d/%d positive)', $meta['label'], $accuracy * 100, $yes, $samples));
        }

        $this->newLine();
        $this->info('✓ Procedure-recommendation models trained.');

        return self::SUCCESS;
    }
}
