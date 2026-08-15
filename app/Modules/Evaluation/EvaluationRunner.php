<?php

namespace App\Modules\Evaluation;

use App\Modules\BuildIntake\LocalEvaluationPobImporter;
use Lootwright\Application\AIGateway\DTO\AiBudgetReservation;
use Lootwright\Application\AIGateway\DTO\AiCallAudit;
use Lootwright\Application\AIGateway\DTO\AiGatewayConfiguration;
use Lootwright\Application\AIGateway\DTO\AiRequestContext;
use Lootwright\Application\AIGateway\DTO\BuildIntentCandidate;
use Lootwright\Application\AIGateway\DTO\ClarificationSet;
use Lootwright\Application\AIGateway\DTO\IntentVocabulary;
use Lootwright\Application\AIGateway\DTO\NaturalLanguageIntentRequest;
use Lootwright\Application\AIGateway\DTO\StructuredAiRequest;
use Lootwright\Application\AIGateway\DTO\StructuredAiResponse;
use Lootwright\Application\AIGateway\Exception\AiProviderFailure;
use Lootwright\Application\AIGateway\Ports\AiBudget;
use Lootwright\Application\AIGateway\Ports\AiExecutionPolicy;
use Lootwright\Application\AIGateway\Ports\AiResponseCache;
use Lootwright\Application\AIGateway\Ports\AiTelemetry;
use Lootwright\Application\AIGateway\Ports\StructuredAiProvider;
use Lootwright\Application\AIGateway\Services\ProviderNeutralAiGateway;
use Lootwright\Application\TradePlanning\Exception\ManualRecipeGenerationFailed;
use Lootwright\Application\TradePlanning\Serialization\ManualTradeRecipeSerializer;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\Shared\Value\Locale;
use Lootwright\GameAdapters\PoE1\TradePlanning\Poe1ManualTradeRecipeGenerator;
use RuntimeException;
use Throwable;

final class EvaluationRunner
{
    /** @var array<string, int> */
    private array $counts = [];

    /** @var list<int> */
    private array $latencies = [];

    private int $maximumMemoryDelta = 0;

    private int $maximumInputTokens = 0;

    private int $maximumOutputTokens = 0;

    private int $maximumCostMicroUsd = 0;

    public function __construct(
        private readonly SyntheticTradeCaseFactory $tradeFactory,
        private readonly LocalEvaluationPobImporter $importer,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $cases
     * @param  array<string, int>  $thresholds
     * @return array<string, mixed>
     */
    public function run(array $cases, array $thresholds): array
    {
        $this->counts = [];
        $this->latencies = [];
        $this->maximumMemoryDelta = 0;
        $this->maximumInputTokens = 0;
        $this->maximumOutputTokens = 0;
        $this->maximumCostMicroUsd = 0;
        $results = [];

        foreach ($cases as $case) {
            $started = hrtime(true);
            $memory = memory_get_usage(true);

            try {
                $outcome = $this->evaluate($case);
            } catch (Throwable $exception) {
                $outcome = [
                    'passed' => false,
                    'status' => 'runner_error',
                    'summary' => ['error_type' => $exception::class],
                ];
            }

            $latency = max(0, (int) ceil((hrtime(true) - $started) / 1_000_000));
            $memoryDelta = max(0, memory_get_usage(true) - $memory);
            $this->latencies[] = $latency;
            $this->maximumMemoryDelta = max($this->maximumMemoryDelta, $memoryDelta);
            $private = ($case['_private'] ?? false) === true;
            $id = $this->string($case, 'id');
            $reportId = $private ? 'private:'.substr(hash('sha256', $id), 0, 16) : $id;
            $summary = $outcome['summary'];
            $results[] = [
                'id' => $reportId,
                'kind' => $this->string($case, 'kind'),
                'private' => $private,
                'passed' => $outcome['passed'],
                'status' => $outcome['status'],
                'latency_ms' => $latency,
                'memory_delta_bytes' => $memoryDelta,
                'fingerprint' => hash('sha256', CanonicalJson::encode($summary)),
                'summary' => $summary,
            ];
        }

        $metrics = $this->metrics(count($results));
        $violations = $this->thresholdViolations($metrics, $thresholds);

        return [
            'cases' => $results,
            'metrics' => $metrics,
            'thresholds' => $thresholds,
            'threshold_violations' => $violations,
            'passed' => $violations === []
                && array_filter($results, static fn (array $case): bool => $case['passed'] !== true) === [],
        ];
    }

    /** @param array<string, mixed> $case
     * @return array{passed: bool, status: string, summary: array<string, mixed>}
     */
    private function evaluate(array $case): array
    {
        return match ($this->string($case, 'kind')) {
            'parser' => $this->parser($case),
            'intent' => $this->intent($case),
            'ruleset' => $this->ruleset($case),
            'deterministic' => $this->deterministic($case),
            'trade' => $this->trade($case),
            'parser_replay' => $this->parserReplay($case),
            'deterministic_replay' => $this->deterministicReplay($case),
            'generated_parser_attack' => $this->generatedParserAttack($case),
            default => throw new RuntimeException('Unknown evaluation case kind.'),
        };
    }

    /** @param array<string, mixed> $case
     * @return array{passed: bool, status: string, summary: array<string, mixed>}
     */
    private function parser(array $case): array
    {
        $expected = $this->map($case, 'expected');
        $expectedStatus = $this->string($expected, 'status');
        $xml = $this->fixture($this->string($case, 'fixture'));
        $input = ($case['encoding'] ?? 'xml') === 'pob_code' ? $this->pobCode($xml) : $xml;
        $result = $this->importer->import($input);

        if ($result->isFailure()) {
            $actualStatus = 'safe_failure';
            $errorCode = $result->error()->code->value;
            $passed = $expectedStatus === $actualStatus
                && $errorCode === ($expected['error_code'] ?? null);
            $this->increment('parser_invalid_total');
            if ($passed) {
                $this->increment('parser_safe_failure_correct');
            }

            return [
                'passed' => $passed,
                'status' => $actualStatus,
                'summary' => ['error_code' => $errorCode],
            ];
        }

        $import = $result->value();
        if (! $import instanceof PobImportResult) {
            throw new RuntimeException('Parser evaluation returned an invalid result.');
        }

        $edition = $import->canonicalBuild->edition->value;
        $declared = is_string($case['declared_edition'] ?? null) ? $case['declared_edition'] : null;
        $actualStatus = $declared !== null && $declared !== $edition ? 'edition_mismatch' : 'success';
        $incomplete = $import->canonicalBuild->characterClassId === null
            || $import->canonicalBuild->skills === [];
        $unsupported = count($import->unsupportedFeatures);
        $hostilePreserved = str_contains($import->canonicalBuild->notes, 'Ignore previous instructions')
            && ! str_contains(CanonicalJson::encode($import), '<script>');
        $passed = $actualStatus === $expectedStatus
            && (! isset($expected['edition']) || $expected['edition'] === $edition)
            && (! isset($expected['incomplete']) || $expected['incomplete'] === $incomplete)
            && (! isset($expected['unsupported_disclosed']) || ($expected['unsupported_disclosed'] === ($unsupported > 0)))
            && (! isset($expected['hostile_text_preserved']) || $expected['hostile_text_preserved'] === $hostilePreserved);

        $this->increment('parser_valid_total');
        if ($passed) {
            $this->increment('parser_success_correct');
        }
        if (isset($expected['edition'])) {
            $this->increment('edition_detection_total');
            if ($expected['edition'] === $edition) {
                $this->increment('edition_detection_correct');
            }
        }
        if (($expected['unsupported_disclosed'] ?? false) === true || ($expected['incomplete'] ?? false) === true) {
            $this->increment('unsupported_expected');
            if ($unsupported > 0 || $incomplete) {
                $this->increment('unsupported_disclosed');
            }
        }

        return [
            'passed' => $passed,
            'status' => $actualStatus,
            'summary' => [
                'edition' => $edition,
                'incomplete' => $incomplete,
                'unsupported_count' => $unsupported,
                'hostile_text_safe' => $hostilePreserved,
                'canonical_hash' => hash('sha256', CanonicalJson::encode($import)),
            ],
        ];
    }

    /** @param array<string, mixed> $case
     * @return array{passed: bool, status: string, summary: array<string, mixed>}
     */
    private function intent(array $case): array
    {
        $mode = $this->string($case, 'provider_mode');
        $provider = new EvaluationStructuredProvider($mode, $this->mapOrEmpty($case, 'provider_output'));
        $budget = new EvaluationAiBudget($mode !== 'budget_exceeded');
        $gateway = new ProviderNeutralAiGateway(
            new AiGatewayConfiguration(
                $mode !== 'disabled',
                'evaluation-model',
                'evaluation-model',
                4_000,
                500,
                900,
                7_000,
                'evaluation-2026-08-15.1',
                200_000,
                20_000,
                1_250_000,
                str_repeat('e', 64),
            ),
            $provider,
            new EvaluationAiPolicy,
            $budget,
            new EvaluationAiCache,
            new EvaluationAiTelemetry,
        );
        $edition = GameEdition::from($this->string($case, 'edition'));
        $locale = $this->value(Locale::from($this->string($case, 'locale')), Locale::class);
        $vocabulary = new IntentVocabulary(
            $edition,
            'fixture.patch',
            'fixture-rules-1',
            str_repeat('a', 64),
            ['mapping', 'bossing', 'delve', 'simulacrum', 'sanctum', 'progression'],
            ['melee', 'ranged', 'spell', 'minion', 'damage_over_time', 'aura_support', 'hybrid'],
            ['budget.low', 'budget.medium', 'budget.high', 'survivability.required', 'damage.maximum'],
        );
        $outcome = $gateway->extractIntent(new NaturalLanguageIntentRequest(
            $this->string($case, 'description'),
            $locale,
            $vocabulary,
            new AiRequestContext(str_repeat('1', 64), str_repeat('2', 64), true, false),
        ));
        $expected = $this->map($case, 'expected');
        $expectedStatus = $this->string($expected, 'status');
        $candidate = $outcome->value instanceof BuildIntentCandidate ? $outcome->value : null;
        $clarification = $outcome->value instanceof ClarificationSet;
        $actualStatus = $candidate instanceof BuildIntentCandidate ? 'candidate' : ($clarification ? 'clarification' : 'invalid');
        if ($expectedStatus === 'safe_fallback' && $clarification) {
            $actualStatus = 'safe_fallback';
        }
        $providerCalled = ($outcome->metadata['provider_called'] ?? false) === true;
        $canonicalResolved = $candidate === null || (
            in_array($candidate->contentGoal, $vocabulary->contentGoals, true)
            && in_array($candidate->playStyle, $vocabulary->playStyles, true)
        );
        $hallucinatedAccepted = $candidate !== null && ! $canonicalResolved ? 1 : 0;
        $constraintCode = $candidate?->constraints[0]['code'] ?? null;
        $passed = $actualStatus === $expectedStatus
            && (! isset($expected['content_goal']) || $candidate?->contentGoal === $expected['content_goal'])
            && (! isset($expected['play_style']) || $candidate?->playStyle === $expected['play_style'])
            && (! isset($expected['constraint_code']) || $constraintCode === $expected['constraint_code'])
            && (! isset($expected['provider_called']) || $providerCalled === $expected['provider_called'])
            && (! isset($expected['hallucinated_ids_accepted']) || $hallucinatedAccepted === $expected['hallucinated_ids_accepted']);

        if (in_array($mode, ['valid', 'low_confidence'], true) && $providerCalled) {
            $this->increment('ai_expected_valid_total');
            if (($outcome->metadata['validation_outcome'] ?? null) === 'valid') {
                $this->increment('ai_schema_valid');
            }
            if ($canonicalResolved) {
                $this->increment('ai_canonical_resolution_valid');
            }
        }
        $this->counts['hallucinated_ids_accepted'] = ($this->counts['hallucinated_ids_accepted'] ?? 0) + $hallucinatedAccepted;
        $this->maximumInputTokens = max($this->maximumInputTokens, $provider->maximumInputTokens);
        $this->maximumOutputTokens = max($this->maximumOutputTokens, $provider->maximumOutputTokens);
        $this->maximumCostMicroUsd = max($this->maximumCostMicroUsd, $budget->maximumSettledMicroUsd);

        return [
            'passed' => $passed,
            'status' => $actualStatus,
            'summary' => [
                'gateway_status' => $outcome->status,
                'provider_called' => $providerCalled,
                'validation_outcome' => $outcome->metadata['validation_outcome'] ?? 'not_called',
                'candidate_content_goal' => $candidate?->contentGoal,
                'candidate_play_style' => $candidate?->playStyle,
                'constraint_code' => $constraintCode,
                'hallucinated_ids_accepted' => $hallucinatedAccepted,
            ],
        ];
    }

    /** @param array<string, mixed> $case
     * @return array{passed: bool, status: string, summary: array<string, mixed>}
     */
    private function ruleset(array $case): array
    {
        $requested = $this->map($case, 'requested');
        $active = $this->map($case, 'active');
        $status = $requested['edition'] !== $active['edition']
            ? 'edition_mismatch'
            : (($requested['version'] !== $active['version'] || $requested['checksum'] !== $active['checksum'])
                ? 'stale_ruleset'
                : 'exact');
        $expected = $this->map($case, 'expected');

        return [
            'passed' => $status === $expected['status'],
            'status' => $status,
            'summary' => ['resolution' => $status],
        ];
    }

    /** @param array<string, mixed> $case
     * @return array{passed: bool, status: string, summary: array<string, mixed>}
     */
    private function deterministic(array $case): array
    {
        $actual = $this->structuralDeterministic($case);
        $expected = $this->map($case, 'expected');
        $expectedFindings = $this->stringList($expected['findings'] ?? null);
        $expectedRecommendations = $this->stringList($expected['recommendations'] ?? null);
        $actualFindings = array_column($actual['findings'], 'code');
        $actualRecommendations = array_column($actual['recommendations'], 'code');
        $truePositives = count(array_intersect($actualFindings, $expectedFindings));
        $this->counts['finding_true_positives'] = ($this->counts['finding_true_positives'] ?? 0) + $truePositives;
        $this->counts['finding_generated_total'] = ($this->counts['finding_generated_total'] ?? 0) + count($actualFindings);
        $this->counts['recommendation_trace_total'] = ($this->counts['recommendation_trace_total'] ?? 0) + count($actual['recommendations']);
        $this->counts['recommendation_trace_complete'] = ($this->counts['recommendation_trace_complete'] ?? 0) + count(array_filter(
            $actual['recommendations'],
            static fn (array $recommendation): bool => $recommendation['trace_complete'],
        ));
        $this->counts['forbidden_cross_edition_recommendations'] = ($this->counts['forbidden_cross_edition_recommendations'] ?? 0)
            + $actual['cross_edition_recommendations'];
        $expectedUnsupported = (int) ($expected['unsupported_disclosures'] ?? 0);
        if ($expectedUnsupported > 0) {
            $this->counts['unsupported_expected'] = ($this->counts['unsupported_expected'] ?? 0) + $expectedUnsupported;
            $this->counts['unsupported_disclosed'] = ($this->counts['unsupported_disclosed'] ?? 0) + min($expectedUnsupported, $actual['unsupported_disclosures']);
        }
        $replayEqual = CanonicalJson::encode($actual) === CanonicalJson::encode($this->structuralDeterministic($case));
        $this->increment('replay_total');
        if ($replayEqual) {
            $this->increment('replay_equal');
        }
        $passed = $actualFindings === $expectedFindings
            && $actualRecommendations === $expectedRecommendations
            && $actual['unsupported_disclosures'] === $expectedUnsupported
            && $actual['cross_edition_recommendations'] === 0
            && $replayEqual;

        return [
            'passed' => $passed,
            'status' => $passed ? 'matched' : 'mismatch',
            'summary' => [
                'scope' => 'fixture_structural',
                'production_engine_status' => 'unavailable',
                'findings' => $actualFindings,
                'recommendations' => $actualRecommendations,
                'unsupported_disclosures' => $actual['unsupported_disclosures'],
                'replay_equal' => $replayEqual,
            ],
        ];
    }

    /** @param array<string, mixed> $case
     * @return array{passed: bool, status: string, summary: array<string, mixed>}
     */
    private function trade(array $case): array
    {
        $mode = $this->string($case, 'mode');
        $expected = $this->map($case, 'expected');

        try {
            $recipe = (new Poe1ManualTradeRecipeGenerator)->generate($this->tradeFactory->request($mode));
            $serialized = ManualTradeRecipeSerializer::canonicalJson($recipe);
            $traced = 0;
            $traceTotal = 0;
            foreach ([$recipe->strict, $recipe->broadFallback] as $variant) {
                foreach ([...$variant->required, ...$variant->weighted, ...$variant->excluded] as $filter) {
                    $traceTotal++;
                    if ($filter->findingCode !== '' && $filter->trace->steps !== []) {
                        $traced++;
                    }
                }
            }
            foreach ($recipe->dependencies as $dependency) {
                $traceTotal++;
                if ($dependency->findingCode !== '' && $dependency->trace->steps !== []) {
                    $traced++;
                }
            }
            $this->counts['trade_trace_total'] = ($this->counts['trade_trace_total'] ?? 0) + $traceTotal;
            $this->counts['trade_trace_complete'] = ($this->counts['trade_trace_complete'] ?? 0) + $traced;
            $networkViolations = preg_match('~/api/trade/|https?://[^" ]+[?]~i', $serialized) === 1 ? 1 : 0;
            $this->counts['undocumented_endpoint_or_network_calls'] = ($this->counts['undocumented_endpoint_or_network_calls'] ?? 0) + $networkViolations;
            $summary = [
                'strict_minimum' => $recipe->strict->required[0]->range?->minimum,
                'broad_minimum' => $recipe->broadFallback->required[0]->range?->minimum,
                'strict_weight' => $recipe->strict->weighted[0]->weight,
                'broad_weight' => $recipe->broadFallback->weighted[0]->weight,
                'unresolved_count' => count($recipe->unresolvedRequirements),
                'trace_complete' => $traceTotal === $traced,
                'network_violations' => $networkViolations,
            ];
            $passed = ($expected['status'] ?? null) === 'success';
            foreach (['strict_minimum', 'broad_minimum', 'strict_weight', 'broad_weight', 'unresolved_count'] as $field) {
                if (array_key_exists($field, $expected) && $summary[$field] !== $expected[$field]) {
                    $passed = false;
                }
            }

            return ['passed' => $passed, 'status' => 'success', 'summary' => $summary];
        } catch (ManualRecipeGenerationFailed $exception) {
            $passed = ($expected['status'] ?? null) === 'safe_failure'
                && ($expected['failure_code'] ?? null) === $exception->failureCode;

            return [
                'passed' => $passed,
                'status' => 'safe_failure',
                'summary' => ['failure_code' => $exception->failureCode],
            ];
        }
    }

    /** @param array<string, mixed> $case
     * @return array{passed: bool, status: string, summary: array<string, mixed>}
     */
    private function parserReplay(array $case): array
    {
        $xml = $this->fixture($this->string($case, 'fixture'));
        $iterations = min(200, max(2, (int) ($case['iterations'] ?? 2)));
        $hashes = [];
        for ($index = 0; $index < $iterations; $index++) {
            $result = $this->importer->import($xml);
            if ($result->isFailure()) {
                return ['passed' => false, 'status' => 'unexpected_failure', 'summary' => ['iteration' => $index]];
            }
            $hashes[] = hash('sha256', CanonicalJson::encode($result->value()));
        }
        $equal = count(array_unique($hashes)) === 1;
        $this->increment('replay_total');
        if ($equal) {
            $this->increment('replay_equal');
        }

        return ['passed' => $equal, 'status' => $equal ? 'equal' : 'mismatch', 'summary' => ['iterations' => $iterations, 'equality' => $equal]];
    }

    /** @param array<string, mixed> $case
     * @return array{passed: bool, status: string, summary: array<string, mixed>}
     */
    private function deterministicReplay(array $case): array
    {
        $iterations = min(200, max(2, (int) ($case['iterations'] ?? 2)));
        $hashes = [];
        for ($index = 0; $index < $iterations; $index++) {
            $hashes[] = hash('sha256', CanonicalJson::encode($this->structuralDeterministic($case)));
        }
        $equal = count(array_unique($hashes)) === 1;
        $this->increment('replay_total');
        if ($equal) {
            $this->increment('replay_equal');
        }

        return ['passed' => $equal, 'status' => $equal ? 'equal' : 'mismatch', 'summary' => ['iterations' => $iterations, 'equality' => $equal]];
    }

    /** @param array<string, mixed> $case
     * @return array{passed: bool, status: string, summary: array<string, mixed>}
     */
    private function generatedParserAttack(array $case): array
    {
        $attack = $this->string($case, 'attack');
        if ($attack === 'deep_xml') {
            $input = '<PathOfBuilding><Build/><A><B><C><D/></C></B></A></PathOfBuilding>';
            $result = $this->importer->import($input, new ImportLimits(xmlDepth: 4));
        } elseif ($attack === 'decompression_bomb') {
            $compressed = gzcompress(str_repeat('A', 8_192), 9);
            if (! is_string($compressed)) {
                throw new RuntimeException('Could not construct the synthetic bomb.');
            }
            $input = rtrim(strtr(base64_encode($compressed), '+/', '-_'), '=');
            $result = $this->importer->import($input, new ImportLimits(inputBytes: 20_000, compressedBytes: 20_000, xmlBytes: 1_024, expansionRatio: 8));
        } else {
            throw new RuntimeException('Unknown generated parser attack.');
        }
        $expected = $this->map($case, 'expected');
        $code = $result->isFailure() ? $result->error()->code->value : 'none';
        $passed = $result->isFailure() && $code === ($expected['error_code'] ?? null);
        $this->increment('parser_invalid_total');
        if ($passed) {
            $this->increment('parser_safe_failure_correct');
        }

        return ['passed' => $passed, 'status' => $passed ? 'safe_failure' : 'unexpected', 'summary' => ['error_code' => $code]];
    }

    /** @param array<string, mixed> $case
     * @return array{findings: list<array{code: string, trace_complete: bool}>, recommendations: list<array{code: string, edition: string, trace_complete: bool}>, unsupported_disclosures: int, cross_edition_recommendations: int}
     */
    private function structuralDeterministic(array $case): array
    {
        $edition = $this->string($case, 'edition');
        $facts = $case['facts'] ?? null;
        if (! is_array($facts) || ! array_is_list($facts)) {
            throw new RuntimeException('Deterministic evaluation facts must be a list.');
        }
        $findings = [];
        $recommendations = [];
        $unsupported = 0;
        $crossEdition = 0;

        foreach ($facts as $fact) {
            if (! is_array($fact)) {
                throw new RuntimeException('Deterministic evaluation fact must be an object.');
            }
            $factEdition = $this->string($fact, 'edition');
            $code = $this->string($fact, 'code');
            $supported = ($fact['supported'] ?? false) === true;
            if (! $supported || $factEdition !== $edition) {
                $unsupported++;

                continue;
            }
            $findings[] = ['code' => 'finding.'.$code, 'trace_complete' => true];
            $recommendations[] = ['code' => 'recommendation.'.$code, 'edition' => $edition, 'trace_complete' => true];
        }

        foreach ($recommendations as $recommendation) {
            if ($recommendation['edition'] !== $edition) {
                $crossEdition++;
            }
        }

        return [
            'findings' => $findings,
            'recommendations' => $recommendations,
            'unsupported_disclosures' => $unsupported,
            'cross_edition_recommendations' => $crossEdition,
        ];
    }

    /** @return array<string, int> */
    private function metrics(int $caseCount): array
    {
        sort($this->latencies, SORT_NUMERIC);
        $p95Index = $this->latencies === [] ? 0 : (int) ceil(count($this->latencies) * 0.95) - 1;

        return [
            'case_count' => $caseCount,
            'parser_success_rate_basis_points' => $this->rate('parser_success_correct', 'parser_valid_total'),
            'parser_safe_failure_rate_basis_points' => $this->rate('parser_safe_failure_correct', 'parser_invalid_total'),
            'edition_detection_precision_basis_points' => $this->rate('edition_detection_correct', 'edition_detection_total'),
            'deterministic_finding_precision_basis_points' => $this->rate('finding_true_positives', 'finding_generated_total'),
            'forbidden_cross_edition_recommendations' => $this->counts['forbidden_cross_edition_recommendations'] ?? 0,
            'unsupported_disclosure_rate_basis_points' => $this->rate('unsupported_disclosed', 'unsupported_expected'),
            'recommendation_trace_completeness_basis_points' => $this->rate('recommendation_trace_complete', 'recommendation_trace_total'),
            'trade_trace_completeness_basis_points' => $this->rate('trade_trace_complete', 'trade_trace_total'),
            'undocumented_endpoint_or_network_calls' => $this->counts['undocumented_endpoint_or_network_calls'] ?? 0,
            'ai_schema_validity_basis_points' => $this->rate('ai_schema_valid', 'ai_expected_valid_total'),
            'ai_canonical_id_resolution_basis_points' => $this->rate('ai_canonical_resolution_valid', 'ai_expected_valid_total'),
            'hallucinated_canonical_ids_accepted' => $this->counts['hallucinated_ids_accepted'] ?? 0,
            'deterministic_replay_equality_basis_points' => $this->rate('replay_equal', 'replay_total'),
            'case_latency_max_ms' => $this->latencies === [] ? 0 : max($this->latencies),
            'case_latency_p95_ms' => $this->latencies[$p95Index] ?? 0,
            'case_memory_delta_max_bytes' => $this->maximumMemoryDelta,
            'estimated_input_tokens_per_call_max' => $this->maximumInputTokens,
            'estimated_output_tokens_per_call_max' => $this->maximumOutputTokens,
            'estimated_cost_per_call_micro_usd_max' => $this->maximumCostMicroUsd,
        ];
    }

    /** @param array<string, int> $metrics
     * @param  array<string, int>  $thresholds
     * @return list<array{metric: string, actual: int, operator: string, threshold: int}>
     */
    private function thresholdViolations(array $metrics, array $thresholds): array
    {
        $maximumMetrics = [
            'forbidden_cross_edition_recommendations_max' => 'forbidden_cross_edition_recommendations',
            'undocumented_endpoint_or_network_calls_max' => 'undocumented_endpoint_or_network_calls',
            'hallucinated_canonical_ids_accepted_max' => 'hallucinated_canonical_ids_accepted',
            'case_latency_max_ms' => 'case_latency_max_ms',
            'case_memory_delta_max_bytes' => 'case_memory_delta_max_bytes',
            'estimated_input_tokens_per_call_max' => 'estimated_input_tokens_per_call_max',
            'estimated_output_tokens_per_call_max' => 'estimated_output_tokens_per_call_max',
            'estimated_cost_per_call_micro_usd_max' => 'estimated_cost_per_call_micro_usd_max',
        ];
        $violations = [];
        foreach ($thresholds as $name => $threshold) {
            $maximum = array_key_exists($name, $maximumMetrics);
            $metric = $maximumMetrics[$name] ?? $name;
            $actual = $metrics[$metric] ?? null;
            if (! is_int($actual)) {
                $violations[] = ['metric' => $metric, 'actual' => -1, 'operator' => 'present', 'threshold' => $threshold];

                continue;
            }
            if (($maximum && $actual > $threshold) || (! $maximum && $actual < $threshold)) {
                $violations[] = [
                    'metric' => $metric,
                    'actual' => $actual,
                    'operator' => $maximum ? '<=' : '>=',
                    'threshold' => $threshold,
                ];
            }
        }

        return $violations;
    }

    private function rate(string $numerator, string $denominator): int
    {
        $total = $this->counts[$denominator] ?? 0;

        return $total === 0 ? 10_000 : intdiv(($this->counts[$numerator] ?? 0) * 10_000, $total);
    }

    private function increment(string $name): void
    {
        $this->counts[$name] = ($this->counts[$name] ?? 0) + 1;
    }

    private function fixture(string $relative): string
    {
        $path = realpath(base_path($relative));
        $fixtureRoot = realpath(base_path('evals/fixtures'));
        $privateRoot = realpath((string) config('evaluation.private_fixtures_directory'));
        if ($path === false || $fixtureRoot === false
            || (! str_starts_with($path, $fixtureRoot.DIRECTORY_SEPARATOR)
                && ($privateRoot === false || ! str_starts_with($path, $privateRoot.DIRECTORY_SEPARATOR)))
        ) {
            throw new RuntimeException('Evaluation fixture path is outside approved roots.');
        }
        $contents = file_get_contents($path);
        if (! is_string($contents) || strlen($contents) > 1_048_576) {
            throw new RuntimeException('Evaluation fixture is unreadable or too large.');
        }

        return $contents;
    }

    private function pobCode(string $xml): string
    {
        $compressed = gzcompress($xml, 9);
        if (! is_string($compressed)) {
            throw new RuntimeException('Could not encode the synthetic PoB fixture.');
        }

        return rtrim(strtr(base64_encode($compressed), '+/', '-_'), '=');
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (! is_string($value)) {
            throw new RuntimeException("Evaluation field {$key} must be a string.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function map(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        if (! is_array($value) || array_is_list($value)) {
            throw new RuntimeException("Evaluation field {$key} must be an object.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function mapOrEmpty(array $data, string $key): array
    {
        $value = $data[$key] ?? [];

        return is_array($value) && ! array_is_list($value) ? $value : [];
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw new RuntimeException('Evaluation expected list must be a list.');
        }
        foreach ($value as $item) {
            if (! is_string($item)) {
                throw new RuntimeException('Evaluation expected list entries must be strings.');
            }
        }

        return $value;
    }

    /**
     * @template TObject of object
     *
     * @param  class-string<TObject>  $expected
     * @return TObject
     */
    private function value(DomainResult $result, string $expected): object
    {
        if ($result->isFailure() || ! $result->value() instanceof $expected) {
            throw new RuntimeException("Could not construct evaluation value {$expected}.");
        }

        return $result->value();
    }
}

final class EvaluationStructuredProvider implements StructuredAiProvider
{
    public int $maximumInputTokens = 0;

    public int $maximumOutputTokens = 0;

    private int $calls = 0;

    /** @param array<string, mixed> $output */
    public function __construct(private readonly string $mode, private readonly array $output) {}

    public function respond(StructuredAiRequest $request): StructuredAiResponse
    {
        $this->calls++;
        if ($this->mode === 'timeout') {
            throw new AiProviderFailure('timeout', true);
        }

        $output = match ($this->mode) {
            'malformed' => '{',
            'hallucinated_id' => json_encode([
                'edition' => 'poe1',
                'content_goal' => 'invented.goal',
                'play_style' => 'melee',
                'constraints' => [],
                'confidence_basis_points' => 9_000,
            ], JSON_THROW_ON_ERROR),
            'low_confidence' => $this->calls === 1
                ? json_encode(['edition' => 'poe1', ...$this->output], JSON_THROW_ON_ERROR)
                : json_encode([
                    'language' => 'tr',
                    'questions' => [[
                        'code' => 'constraints',
                        'question' => 'Dayanıklılık ile azami hasar arasında hangisi öncelikli?',
                    ]],
                ], JSON_THROW_ON_ERROR),
            default => json_encode(['edition' => 'poe1', ...$this->output], JSON_THROW_ON_ERROR),
        };
        $inputTokens = 250;
        $outputTokens = 100;
        $this->maximumInputTokens = max($this->maximumInputTokens, $inputTokens);
        $this->maximumOutputTokens = max($this->maximumOutputTokens, $outputTokens);

        return new StructuredAiResponse(
            'fake',
            $request->model,
            $output,
            false,
            $inputTokens,
            0,
            $outputTokens,
            1,
            false,
        );
    }
}

final class EvaluationAiBudget implements AiBudget
{
    public int $maximumSettledMicroUsd = 0;

    public function __construct(private readonly bool $allowed) {}

    public function reserve(AiRequestContext $context, int $maximumMicroUsd): ?AiBudgetReservation
    {
        return $this->allowed ? new AiBudgetReservation('evaluation', $maximumMicroUsd) : null;
    }

    public function settle(AiBudgetReservation $reservation, int $actualMicroUsd): void
    {
        $this->maximumSettledMicroUsd = max($this->maximumSettledMicroUsd, $actualMicroUsd);
    }

    public function cancel(AiBudgetReservation $reservation): void {}
}

final readonly class EvaluationAiPolicy implements AiExecutionPolicy
{
    public function permits(string $task): bool
    {
        return true;
    }
}

final class EvaluationAiCache implements AiResponseCache
{
    public function get(string $key, string $userHash): ?array
    {
        return null;
    }

    public function put(string $key, string $userHash, array $value): void {}
}

final class EvaluationAiTelemetry implements AiTelemetry
{
    public function record(AiCallAudit $audit): void {}
}
