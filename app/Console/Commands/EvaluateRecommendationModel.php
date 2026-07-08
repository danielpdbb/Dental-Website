<?php

namespace App\Console\Commands;

use App\Services\ML\ProcedureDatasetGenerator;
use App\Services\ML\ProcedureRecommendationModel;
use Illuminate\Console\Command;
use Rubix\ML\Classifiers\LogisticRegression;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Pipeline;
use Rubix\ML\Transformers\ZScaleStandardizer;

/**
 * Evaluates the procedure-recommendation logistic-regression models on a held-out
 * test split and reports BOTH families of metrics:
 *
 *  - Classification (on the yes/no decision): Accuracy, Precision, Recall, F1
 *  - Regression (on the predicted probability vs the 0/1 outcome): MAE, MSE, R²
 *    (MSE of a probability against a 0/1 label is the Brier score.)
 *
 * Run: php artisan ml:recommend:evaluate
 */
class EvaluateRecommendationModel extends Command
{
    protected $signature = 'ml:recommend:evaluate {--samples=2000 : Synthetic samples to evaluate on}';

    protected $description = 'Evaluate the procedure-recommendation models (accuracy, precision, MAE, MSE, R²)';

    public function handle(ProcedureDatasetGenerator $generator): int
    {
        $samples = (int) $this->option('samples');
        $data = $generator->generate($samples);

        $this->line("Evaluating on {$samples} samples (80% train / 20% held-out test).");
        $this->newLine();
        $this->line(sprintf('  %-22s %8s %9s %7s %6s | %7s %7s %7s', 'Procedure', 'Accuracy', 'Precision', 'Recall', 'F1', 'MAE', 'MSE', 'R²'));
        $this->line('  '.str_repeat('-', 82));

        foreach (ProcedureRecommendationModel::TARGETS as $target => $meta) {
            $dataset = new Labeled($data['samples'], $data['labels'][$target]);
            [$training, $testing] = $dataset->stratifiedSplit(0.8);

            // Same pipeline as training (standardize features, then logistic regression).
            $estimator = new Pipeline([new ZScaleStandardizer], new LogisticRegression);
            $estimator->train($training);

            $predictions = $estimator->predict($testing);   // 'yes' / 'no'
            $probabilities = $estimator->proba($testing);    // ['yes' => p, 'no' => 1-p]
            $labels = $testing->labels();

            $m = $this->metrics($labels, $predictions, $probabilities);

            $this->line(sprintf(
                '  %-22s %7.1f%% %8.1f%% %6.1f%% %5.2f | %7.4f %7.4f %7.3f',
                $meta['label'],
                $m['accuracy'] * 100, $m['precision'] * 100, $m['recall'] * 100, $m['f1'],
                $m['mae'], $m['mse'], $m['r2'],
            ));
        }

        $this->newLine();
        $this->line('Accuracy/Precision/Recall/F1 use the yes/no decision (positive class = "yes").');
        $this->line('MAE/MSE/R² use the predicted probability vs the actual outcome (1=yes, 0=no). MSE = Brier score.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $labels      actual 'yes'/'no'
     * @param  list<string>  $predictions predicted 'yes'/'no'
     * @param  list<array<string,float>>  $proba  per-sample class probabilities
     * @return array<string,float>
     */
    private function metrics(array $labels, array $predictions, array $proba): array
    {
        $tp = $fp = $tn = $fn = 0;
        $absErr = $sqErr = 0.0;
        $ys = [];

        foreach ($labels as $i => $y) {
            $yhat = $predictions[$i];
            $p = (float) ($proba[$i]['yes'] ?? 0.0); // predicted probability of "yes"
            $yi = $y === 'yes' ? 1.0 : 0.0;          // actual outcome as 0/1

            // Confusion matrix (positive class = "yes").
            if ($yhat === 'yes' && $y === 'yes') {
                $tp++;
            } elseif ($yhat === 'yes' && $y === 'no') {
                $fp++;
            } elseif ($yhat === 'no' && $y === 'no') {
                $tn++;
            } else {
                $fn++;
            }

            // Regression-style error on the probability.
            $absErr += abs($p - $yi);
            $sqErr += ($p - $yi) ** 2;
            $ys[] = $yi;
        }

        $n = max(1, count($labels));
        $accuracy = ($tp + $tn) / $n;
        $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0.0;
        $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0.0;
        $f1 = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0.0;

        $mae = $absErr / $n;
        $mse = $sqErr / $n;

        // R² = 1 - SS_res / SS_tot
        $mean = array_sum($ys) / $n;
        $ssTot = array_sum(array_map(fn ($y) => ($y - $mean) ** 2, $ys));
        $r2 = $ssTot > 0 ? 1 - ($sqErr / $ssTot) : 0.0;

        return compact('accuracy', 'precision', 'recall', 'f1', 'mae', 'mse', 'r2');
    }
}
