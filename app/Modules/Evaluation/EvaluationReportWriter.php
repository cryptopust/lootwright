<?php

namespace App\Modules\Evaluation;

use Illuminate\Filesystem\Filesystem;
use JsonException;
use RuntimeException;

final readonly class EvaluationReportWriter
{
    public function __construct(private Filesystem $files) {}

    /**
     * @param  array<string, mixed>  $run
     * @param  list<array<string, mixed>>  $regressions
     * @return array{json: string, markdown: string}
     */
    public function write(string $suite, string $sourceHash, array $run, array $regressions): array
    {
        $directory = (string) config('evaluation.reports_directory');
        $this->files->ensureDirectoryExists($directory, 0700);
        $report = [
            'schema_version' => (string) config('evaluation.schema_version'),
            'suite_version' => (string) config('evaluation.suite_version'),
            'suite' => $suite,
            'generated_at' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'source_hash_sha256' => $sourceHash,
            'scope_notice' => 'Production rulesets and deterministic game analysis remain unavailable; finding metrics marked fixture_structural are harness evaluations only.',
            ...$run,
            'regression_diffs' => $regressions,
        ];
        $jsonPath = $directory.DIRECTORY_SEPARATOR.$suite.'-latest.json';
        $markdownPath = $directory.DIRECTORY_SEPARATOR.$suite.'-latest.md';
        $this->files->put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);
        $this->files->put($markdownPath, $this->markdown($report));

        return ['json' => $jsonPath, 'markdown' => $markdownPath];
    }

    /** @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function stableSnapshot(string $suite, string $sourceHash, array $run): array
    {
        $cases = [];
        foreach ($run['cases'] ?? [] as $case) {
            if (! is_array($case)) {
                continue;
            }
            if ($suite === 'extended' && ! str_starts_with((string) ($case['id'] ?? ''), 'extended.')) {
                continue;
            }
            $cases[] = [
                'id' => $case['id'] ?? null,
                'kind' => $case['kind'] ?? null,
                'passed' => $case['passed'] ?? null,
                'status' => $case['status'] ?? null,
                'fingerprint' => $case['fingerprint'] ?? null,
            ];
        }

        $metrics = $run['metrics'] ?? [];
        if (is_array($metrics)) {
            unset($metrics['case_latency_max_ms'], $metrics['case_latency_p95_ms'], $metrics['case_memory_delta_max_bytes']);
        }

        return [
            'schema_version' => (string) config('evaluation.schema_version'),
            'suite_version' => (string) config('evaluation.suite_version'),
            'suite' => $suite,
            'source_hash_sha256' => $sourceHash,
            'cases' => $cases,
            'metrics' => $metrics,
        ];
    }

    /** @param array<string, mixed> $current
     * @return list<array<string, mixed>>
     */
    public function regressions(string $suite, array $current): array
    {
        $path = base_path('evals/baselines/'.$suite.'.json');
        if (! $this->files->exists($path)) {
            return [['path' => '$', 'expected' => 'version-controlled baseline', 'actual' => 'missing']];
        }

        try {
            $baseline = json_decode($this->files->get($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [['path' => '$', 'expected' => 'valid baseline JSON', 'actual' => 'invalid']];
        }
        if (! is_array($baseline)) {
            return [['path' => '$', 'expected' => 'baseline object', 'actual' => 'invalid']];
        }
        unset($baseline['review']);

        return $this->diff($baseline, $current);
    }

    /** @param array<string, mixed> $snapshot */
    public function updateBaseline(string $suite, array $snapshot, string $reviewer, string $reason): string
    {
        if (preg_match('/^[A-Za-z0-9._-]{3,64}$/D', $reviewer) !== 1 || mb_strlen(trim($reason)) < 20) {
            throw new RuntimeException('Baseline updates require a reviewer identifier and a specific reason of at least 20 characters.');
        }
        $path = base_path('evals/baselines/'.$suite.'.json');
        $snapshot['review'] = [
            'reviewer' => $reviewer,
            'reason' => trim($reason),
            'reviewed_at' => now()->utc()->format('Y-m-d'),
        ];
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL);

        return $path;
    }

    /** @param array<string, mixed> $report */
    private function markdown(array $report): string
    {
        $lines = [
            '# Lootwright Evaluation Report',
            '',
            '- Suite: `'.($report['suite'] ?? 'unknown').'`',
            '- Suite version: `'.($report['suite_version'] ?? 'unknown').'`',
            '- Generated: `'.($report['generated_at'] ?? 'unknown').'`',
            '- Result: **'.(($report['passed'] ?? false) ? 'PASS' : 'FAIL').'**',
            '',
            '> '.($report['scope_notice'] ?? ''),
            '',
            '## Metrics',
            '',
            '| Metric | Value |',
            '| --- | ---: |',
        ];
        foreach ($report['metrics'] ?? [] as $name => $value) {
            $lines[] = '| `'.$name.'` | '.$value.' |';
        }
        $lines[] = '';
        $lines[] = '## Cases';
        $lines[] = '';
        $lines[] = '| Case | Kind | Result | Status | Latency ms | Memory bytes |';
        $lines[] = '| --- | --- | --- | --- | ---: | ---: |';
        foreach ($report['cases'] ?? [] as $case) {
            $lines[] = '| `'.$case['id'].'` | `'.$case['kind'].'` | '.($case['passed'] ? 'PASS' : 'FAIL').' | `'.$case['status'].'` | '.$case['latency_ms'].' | '.$case['memory_delta_bytes'].' |';
        }
        $lines[] = '';
        $lines[] = '## Regression diffs';
        $lines[] = '';
        if (($report['regression_diffs'] ?? []) === []) {
            $lines[] = 'No stable structural regressions detected.';
        } else {
            foreach ($report['regression_diffs'] as $diff) {
                $lines[] = '- `'.$diff['path'].'`: expected `'.json_encode($diff['expected']).'`, actual `'.json_encode($diff['actual']).'`';
            }
        }
        $lines[] = '';
        $lines[] = 'Reports contain no raw fixture, prompt, private note, or user identifier.';

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /** @return list<array<string, mixed>> */
    private function diff(mixed $expected, mixed $actual, string $path = '$'): array
    {
        if (is_array($expected) && is_array($actual)) {
            $diffs = [];
            $keys = array_values(array_unique([...array_keys($expected), ...array_keys($actual)]));
            foreach ($keys as $key) {
                $child = $path.'.'.$key;
                if (! array_key_exists($key, $expected)) {
                    $diffs[] = ['path' => $child, 'expected' => null, 'actual' => $actual[$key]];
                } elseif (! array_key_exists($key, $actual)) {
                    $diffs[] = ['path' => $child, 'expected' => $expected[$key], 'actual' => null];
                } else {
                    $diffs = [...$diffs, ...$this->diff($expected[$key], $actual[$key], $child)];
                }
            }

            return $diffs;
        }

        return $expected === $actual ? [] : [['path' => $path, 'expected' => $expected, 'actual' => $actual]];
    }
}
