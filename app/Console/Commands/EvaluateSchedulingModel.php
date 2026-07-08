<?php

namespace App\Console\Commands;

use App\Services\ML\AppointmentFeatureExtractor;
use Illuminate\Console\Command;
use Rubix\ML\Classifiers\ClassificationTree;
use Rubix\ML\Datasets\Labeled;

/**
 * Evaluates the predictive-scheduling Decision Tree on a held-out test split and
 * reports Accuracy, Precision, Recall and F1 plus the confusion matrix. The positive
 * class is "missed" (no-show / cancelled) — the outcome we actually want to catch.
 *
 * Run: php artisan ml:scheduling:evaluate
 */
class EvaluateSchedulingModel extends Command
{
    protected $signature = 'ml:scheduling:evaluate {--depth=6 : Max tree height}';

    protected $description = 'Evaluate the attendance Decision Tree (accuracy, precision, recall, F1)';

    public function handle(AppointmentFeatureExtractor $extractor): int
    {
        [$samples, $labels] = $extractor->trainingData();
        $n = count($samples);

        if ($n < 20) {
            $this->error("Only {$n} labelled appointments — not enough to evaluate.");

            return self::FAILURE;
        }

        $dataset = new Labeled($samples, $labels);
        [$training, $testing] = $dataset->stratifiedSplit(0.8);

        $estimator = new ClassificationTree((int) $this->option('depth'));
        $estimator->train($training);

        $predictions = $estimator->predict($testing);
        $actual = $testing->labels();

        // Confusion matrix, positive class = "missed".
        $tp = $fp = $tn = $fn = 0;
        foreach ($actual as $i => $y) {
            $yhat = $predictions[$i];
            if ($yhat === 'missed' && $y === 'missed') {
                $tp++;
            } elseif ($yhat === 'missed' && $y === 'kept') {
                $fp++;
            } elseif ($yhat === 'kept' && $y === 'kept') {
                $tn++;
            } else {
                $fn++;
            }
        }

        $total = max(1, count($actual));
        $accuracy = ($tp + $tn) / $total;
        $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0.0;
        $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0.0;
        $f1 = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0.0;

        $this->line("Test set: {$total} appointments (positive class = \"missed\").");
        $this->newLine();
        $this->info(sprintf('  Accuracy   %.1f%%', $accuracy * 100));
        $this->info(sprintf('  Precision  %.1f%%   (of those predicted "missed", how many really were)', $precision * 100));
        $this->info(sprintf('  Recall     %.1f%%   (of the real no-shows, how many we caught)', $recall * 100));
        $this->info(sprintf('  F1-score   %.2f', $f1));
        $this->newLine();
        $this->line('  Confusion matrix:');
        $this->line(sprintf('               pred missed   pred kept'));
        $this->line(sprintf('  actual missed   %6d      %6d', $tp, $fn));
        $this->line(sprintf('  actual kept     %6d      %6d', $fp, $tn));

        return self::SUCCESS;
    }
}
