<?php

namespace App\Modules\Evaluation;

use JsonException;
use RuntimeException;

final class EvaluationCaseRepository
{
    /** @return array{cases: list<array<string, mixed>>, source_hash: string} */
    public function load(string $suite, bool $includePrivate): array
    {
        if (! in_array($suite, ['fast', 'extended'], true)) {
            throw new RuntimeException('Local evaluation suite must be fast or extended.');
        }
        if ($includePrivate && $suite !== 'extended') {
            throw new RuntimeException('Private fixtures may run only in the manually invoked extended suite.');
        }

        $paths = [base_path('evals/cases/fast.json')];
        if ($suite === 'extended') {
            $paths[] = base_path('evals/cases/extended.json');
        }

        $cases = [];
        $sourceHashes = [];
        foreach ($paths as $path) {
            $document = $this->document($path);
            $sourceHashes[] = hash_file('sha256', $path) ?: '';
            foreach ($document['cases'] as $case) {
                $cases[] = $this->case($case, false);
            }
        }

        if ($includePrivate) {
            $privateRoot = realpath((string) config('evaluation.private_fixtures_directory'));
            if ($privateRoot === false) {
                throw new RuntimeException('Private evaluation directory does not exist.');
            }
            $privatePaths = glob($privateRoot.DIRECTORY_SEPARATOR.'*.json') ?: [];
            sort($privatePaths, SORT_STRING);
            foreach ($privatePaths as $path) {
                $document = $this->document($path);
                $sourceHashes[] = hash_file('sha256', $path) ?: '';
                foreach ($document['cases'] as $case) {
                    if (($case['user_authorized'] ?? false) !== true) {
                        throw new RuntimeException('Every private evaluation case must record user_authorized=true.');
                    }
                    $cases[] = $this->case($case, true);
                }
            }
        }

        $ids = array_column($cases, 'id');
        if (count($ids) !== count(array_unique($ids))) {
            throw new RuntimeException('Evaluation case IDs must be globally unique.');
        }

        return [
            'cases' => $cases,
            'source_hash' => hash('sha256', implode("\n", $sourceHashes)),
        ];
    }

    /** @return array{schema_version: string, suite_version: string, cases: list<mixed>} */
    private function document(string $path): array
    {
        $contents = file_get_contents($path);
        if (! is_string($contents) || strlen($contents) > 1_048_576) {
            throw new RuntimeException('Evaluation case file is missing or too large.');
        }

        try {
            $document = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Evaluation case file is not valid JSON.', previous: $exception);
        }

        if (! is_array($document) || array_is_list($document)
            || ($document['schema_version'] ?? null) !== config('evaluation.schema_version')
            || ($document['suite_version'] ?? null) !== config('evaluation.suite_version')
            || ! is_array($document['cases'] ?? null)
            || ! array_is_list($document['cases'])
        ) {
            throw new RuntimeException('Evaluation case file does not match the active versioned envelope.');
        }

        return [
            'schema_version' => (string) $document['schema_version'],
            'suite_version' => (string) $document['suite_version'],
            'cases' => $document['cases'],
        ];
    }

    /** @return array<string, mixed> */
    private function case(mixed $value, bool $private): array
    {
        if (! is_array($value) || array_is_list($value)) {
            throw new RuntimeException('Evaluation case must be an object.');
        }
        $id = $value['id'] ?? null;
        $kind = $value['kind'] ?? null;
        if (! is_string($id) || preg_match('/^[a-z][a-z0-9._-]{2,127}$/D', $id) !== 1
            || ! is_string($kind)
            || ! in_array($kind, [
                'parser', 'intent', 'ruleset', 'deterministic', 'trade',
                'parser_replay', 'deterministic_replay', 'generated_parser_attack',
            ], true)
            || ! is_array($value['expected'] ?? null)
            || array_is_list($value['expected'])
        ) {
            throw new RuntimeException('Evaluation case has invalid structural fields.');
        }
        $value['_private'] = $private;

        return $value;
    }
}
