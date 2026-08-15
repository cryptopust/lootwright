<?php

namespace App\Console\Commands;

use App\Modules\Evaluation\EvaluationCaseRepository;
use App\Modules\Evaluation\EvaluationReportWriter;
use App\Modules\Evaluation\EvaluationRunner;
use Illuminate\Console\Command;
use RuntimeException;

final class RunEvaluations extends Command
{
    protected $signature = 'eval:run
        {--suite=fast : fast or extended}
        {--include-private : Include user-authorized ignored fixtures; extended only}
        {--update-baseline : Write a reviewed stable baseline after a passing run}
        {--reviewer= : Reviewer identifier required for a baseline update}
        {--reason= : Specific review reason required for a baseline update}';

    protected $description = 'Run reproducible local structural evaluations and emit JSON/Markdown reports';

    public function handle(
        EvaluationCaseRepository $cases,
        EvaluationRunner $runner,
        EvaluationReportWriter $reports,
    ): int {
        $suite = (string) $this->option('suite');
        $includePrivate = (bool) $this->option('include-private');

        try {
            $loaded = $cases->load($suite, $includePrivate);
            $thresholds = config('evaluation.thresholds');
            if (! is_array($thresholds)) {
                throw new RuntimeException('Evaluation thresholds are not configured.');
            }
            $thresholds = array_map(static fn (mixed $value): int => is_int($value) ? $value : -1, $thresholds);
            $run = $runner->run($loaded['cases'], $thresholds);
            $snapshot = $reports->stableSnapshot($suite, $loaded['source_hash'], $run);

            if ($this->option('update-baseline')) {
                if (($run['passed'] ?? false) !== true || $includePrivate) {
                    throw new RuntimeException('Only a passing public-fixture run may update a baseline.');
                }
                $path = $reports->updateBaseline(
                    $suite,
                    $snapshot,
                    (string) $this->option('reviewer'),
                    (string) $this->option('reason'),
                );
                $this->warn('Baseline updated after explicit review: '.$path);
                $regressions = [];
            } else {
                $regressions = $reports->regressions($suite, $snapshot);
            }

            $run['passed'] = $run['passed'] === true && $regressions === [];
            $paths = $reports->write($suite, $loaded['source_hash'], $run, $regressions);
        } catch (RuntimeException $exception) {
            $this->error('Evaluation refused: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->line('Suite: '.$suite);
        $this->line('Cases: '.count($run['cases'] ?? []));
        $this->line('JSON report: '.$paths['json']);
        $this->line('Markdown report: '.$paths['markdown']);
        $this->line('Result: '.($run['passed'] ? 'PASS' : 'FAIL'));

        return $run['passed'] ? self::SUCCESS : self::FAILURE;
    }
}
