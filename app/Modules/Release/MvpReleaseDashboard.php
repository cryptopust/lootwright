<?php

namespace App\Modules\Release;

use App\Modules\Analysis\Infrastructure\ProductionPoe1DeterministicAnalysisEngine;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Lootwright\Application\GameData\Ports\DataCoverageReporter;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Domain\Shared\Game\GameEdition;
use Throwable;

/**
 * Produces an aggregate, non-sensitive release ledger from production bindings
 * and observed workflow records. It deliberately does not infer a PASS from
 * unit fixtures or architecture-only support.
 */
final readonly class MvpReleaseDashboard
{
    public function __construct(
        private DataCoverageReporter $coverage,
        private Container $container,
    ) {}

    /** @return array<string, mixed> */
    public function report(): array
    {
        $editions = [];

        foreach (GameEdition::cases() as $edition) {
            $editions[$edition->value] = $this->edition($edition);
        }

        return [
            'generated_at' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            // PoE1 is the active release scope. PoE2 retains an independent
            // verdict, but its dormant phase-two status must never block PoE1.
            'overall_status' => $editions['poe1']['status'],
            'active_release_edition' => GameEdition::Poe1->value,
            'scope_notice' => 'PoE1 ve PoE2 bağımsız değerlendirilir. Fixture veya mimari kapsam, oyuncu kabulü yerine geçmez.',
            'editions' => $editions,
            'market_provider' => $this->marketProvider(),
            'regressions' => $this->regressions(),
            'ai' => $this->aiHealth(),
        ];
    }

    /** @return array<string, mixed> */
    private function edition(GameEdition $edition): array
    {
        $rulesetQuery = DB::table('ruleset_activations as activations')
            ->join('ruleset_versions as rulesets', 'rulesets.id', '=', 'activations.ruleset_version_id')
            ->leftJoin('ruleset_dataset_approvals as approvals', 'approvals.ruleset_version_id', '=', 'rulesets.id')
            ->where('activations.game_edition', $edition->value)
            ->orderByDesc('activations.activated_at');
        $rulesetId = $rulesetQuery->value('rulesets.id');
        $activeRuleset = is_string($rulesetId) ? [
            'id' => $rulesetId,
            'version' => $rulesetQuery->value('rulesets.version'),
            'status' => $rulesetQuery->value('rulesets.status'),
            'canonical_payload' => $rulesetQuery->value('rulesets.canonical_payload'),
            'dataset_classification' => $rulesetQuery->value('approvals.dataset_classification'),
            'provenance_status' => $rulesetQuery->value('approvals.provenance_status'),
            'compatibility_status' => $rulesetQuery->value('approvals.compatibility_status'),
        ] : null;
        $analysisQuery = DB::table('analyses')
            ->where('game_edition', $edition->value)
            ->where('state', 'completed')
            ->latest('updated_at');
        $analysisIdValue = $analysisQuery->value('id');
        $analysisId = is_string($analysisIdValue) ? $analysisIdValue : null;
        $artifactIdValue = $analysisId === null ? null : $analysisQuery->value('artifact_id');
        $artifactQuery = is_string($artifactIdValue) ? DB::table('build_artifacts')->where('id', $artifactIdValue) : null;
        $artifactAdapter = $artifactQuery?->value('adapter_key');
        $findingCount = $analysisId === null ? 0 : DB::table('analysis_findings')->where('analysis_id', $analysisId)->count();
        $recommendationCount = $analysisId === null ? 0 : DB::table('analysis_recommendations')->where('analysis_id', $analysisId)->count();
        $traceableRecommendationCount = $analysisId === null ? 0 : $this->traceableRecommendationCount($analysisId);
        $recipeCount = $analysisId === null ? 0 : DB::table('manual_trade_recipes')->where('analysis_id', $analysisId)->count();
        $approved = $activeRuleset !== null
            && $activeRuleset['status'] === 'published'
            && $activeRuleset['dataset_classification'] === 'approved_import'
            && $activeRuleset['provenance_status'] === 'approved'
            && $activeRuleset['compatibility_status'] === 'compatible';
        $productionEngine = $edition === GameEdition::Poe1
            && $this->isProductionEngine($this->container->make(DeterministicAnalysisEngine::class));
        $public = in_array($edition->value, (array) config('game-editions.public', []), true);
        $parserObserved = is_string($artifactAdapter) && $artifactAdapter !== '';
        $unsupported = $this->unsupportedRate($edition);
        $coverage = array_map(static fn ($entry): array => $entry->jsonSerialize(), $this->coverage->forEdition($edition));
        $ruleCoverage = $this->ruleCoverage($activeRuleset['canonical_payload'] ?? null);
        $securityEvidence = $this->evidenceId(config('release-gate.security_acceptance_id'));
        $stagingEvidence = $this->evidenceId(config('release-gate.staging_acceptance.'.$edition->value));

        $gates = [
            $this->gate('public_scope', 'Oyuncu akışında etkin', $public, $public ? 'Public edition allowlist içinde.' : 'Public edition allowlist dışında.', true),
            $this->gate('approved_ruleset', 'Onaylı exact ruleset', $approved, $approved ? 'Published, approved provenance ve compatible.' : 'Aktif approved-import ruleset yok.', true),
            $this->gate(
                'real_build_import',
                'Oyuncu build importu gözlemi',
                $parserObserved && $stagingEvidence !== null,
                ! $parserObserved
                    ? 'Production workflow içinde normalize edilmiş artifact gözlenmedi.'
                    : ($stagingEvidence === null
                        ? 'Parser production workflow içinde çalıştı; gerçek oyuncu buildiyle imzalı staging kanıtı yok.'
                        : 'Production parser gözlendi; reviewed staging evidence: '.$stagingEvidence),
                true,
                'BLOCKED',
            ),
            $this->gate('deterministic_findings', 'Deterministik finding', $findingCount > 0, $findingCount > 0 ? $findingCount.' persisted finding.' : 'Persisted finding üreten tamamlanmış oyuncu analizi yok.', true),
            $this->gate('upgrade_planner', 'Production upgrade planı', $recommendationCount > 0, $recommendationCount > 0 ? $recommendationCount.' persisted recommendation.' : 'Production analiz çıktısında recommendation yok.', true),
            $this->gate(
                'recommendation_trace',
                'Machine-readable recommendation trace',
                $recommendationCount > 0 && $traceableRecommendationCount === $recommendationCount,
                $recommendationCount === 0
                    ? 'Trace doğrulanacak production recommendation yok.'
                    : $traceableRecommendationCount.'/'.$recommendationCount.' recommendation tam karar zinciri taşıyor.',
                true,
            ),
            $this->gate('trade_recipes', 'Production manual Trade recipe', $recipeCount > 0, $recipeCount > 0 ? $recipeCount.' persisted recipe.' : 'Production analiz çıktısında manual Trade recipe yok.', true),
            $this->gate('fixture_free', 'Production fixture guard', $productionEngine && $approved, $productionEngine && $approved ? 'Production engine + approved imported ruleset.' : 'Edition için doğrulanmış production engine/ruleset çifti yok.', true),
            $this->gate('critical_security', 'Kritik güvenlik kabulü', $securityEvidence !== null, $securityEvidence === null ? 'Bu release için reviewed CI güvenlik kabul kaydı yok.' : 'Reviewed evidence: '.$securityEvidence, true, 'BLOCKED'),
            $this->gate('staging_acceptance', 'Manuel staging kabulü', $stagingEvidence !== null, $stagingEvidence === null ? 'Gerçek build ve oyuncu sorularıyla imzalı staging kabul kaydı yok.' : 'Reviewed evidence: '.$stagingEvidence, true, 'BLOCKED'),
        ];
        $failed = array_values(array_filter($gates, static fn (array $gate): bool => $gate['status'] !== 'PASS' && $gate['critical']));

        $limitations = $this->limitations($coverage, $ruleCoverage, $unsupported, $recommendationCount, $recipeCount);

        return [
            'edition' => $edition->value,
            'public' => $public,
            'status' => $failed !== [] ? 'FAIL' : ($limitations === [] ? 'PASS' : 'PASS_WITH_LIMITATIONS'),
            'ruleset' => $activeRuleset === null ? null : ['id' => $activeRuleset['id'], 'version' => $activeRuleset['version']],
            'coverage' => $coverage,
            'parser' => [
                'adapter' => $artifactAdapter,
                'observed_completed_analysis' => $analysisId !== null,
                'coverage_status' => $parserObserved ? 'observed' : 'not_observed',
            ],
            'analysis_rules' => $ruleCoverage,
            'recommendation_trace' => [
                'recommendations' => $recommendationCount,
                'complete_traces' => $traceableRecommendationCount,
            ],
            'trade_vocabulary' => $this->coverageCategory($coverage, 'trade_vocabulary_definition'),
            'unsupported_mechanics' => $unsupported,
            'latencies_ms' => [
                'import' => $this->elapsedMilliseconds($artifactQuery?->value('created_at'), $artifactQuery?->value('updated_at')),
                'analysis_end_to_end' => $this->elapsedMilliseconds($analysisQuery->value('created_at'), $analysisQuery->value('updated_at')),
                'planner' => $recommendationCount > 0 ? 'not_instrumented' : null,
                'trade_recipe' => $recipeCount > 0 ? 'not_instrumented' : null,
            ],
            'gates' => $gates,
            'blockers' => array_map(static fn (array $gate): string => $gate['label'].': '.$gate['evidence'], $failed),
            'limitations' => $limitations,
        ];
    }

    /**
     * A limitation can qualify an otherwise complete edition as
     * PASS_WITH_LIMITATIONS, but it can never hide a failed critical gate.
     *
     * @param  list<array<string, mixed>>  $coverage
     * @param  array<string, mixed>  $ruleCoverage
     * @param  array{sample_size:int,analyses_with_unsupported:int,rate_percent:float|null}  $unsupported
     * @return list<string>
     */
    private function limitations(array $coverage, array $ruleCoverage, array $unsupported, int $recommendationCount, int $recipeCount): array
    {
        $limitations = [];

        if (($ruleCoverage['coverage_percent'] ?? null) === null) {
            $limitations[] = 'Analysis-rule completeness denominator is unknown.';
        }
        $tradeVocabulary = $this->coverageCategory($coverage, 'trade_vocabulary_definition');
        if ($tradeVocabulary === null || ($tradeVocabulary['status'] ?? null) !== 'available') {
            $limitations[] = 'Trade-vocabulary coverage is not observed.';
        }
        if (($unsupported['rate_percent'] ?? null) === null) {
            $limitations[] = 'Unsupported-mechanic rate has no readable production sample.';
        }
        if ($recommendationCount > 0) {
            $limitations[] = 'Planner-stage latency is not separately instrumented.';
        }
        if ($recipeCount > 0) {
            $limitations[] = 'Trade-recipe-stage latency is not separately instrumented.';
        }

        return $limitations;
    }

    private function traceableRecommendationCount(string $analysisId): int
    {
        $required = [
            'user_goal',
            'finding',
            'evidence',
            'rule',
            'upgrade_candidate',
            'constraints',
            'market_evidence',
            'recommendation',
        ];
        $complete = 0;

        foreach (DB::table('analysis_recommendations')->where('analysis_id', $analysisId)->get(['payload_encrypted', 'payload_hash_sha256']) as $row) {
            try {
                $canonical = Crypt::decryptString((string) $row->payload_encrypted);
                if (! hash_equals((string) $row->payload_hash_sha256, hash('sha256', $canonical))) {
                    continue;
                }
                $payload = json_decode($canonical, true, flags: JSON_THROW_ON_ERROR);
                $trace = is_array($payload) ? ($payload['decision_trace'] ?? null) : null;
                if (! is_array($trace) || array_is_list($trace)) {
                    continue;
                }
                if (count(array_diff($required, array_keys($trace))) === 0) {
                    $complete++;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $complete;
    }

    /** @return array<string, mixed> */
    private function marketProvider(): array
    {
        $query = DB::table('external_source_sync_runs')
            ->where('source_key', 'POENINJA-ECONOMY-001')
            ->latest('started_at');
        $status = $query->value('status');

        return [
            'source' => 'POENINJA-ECONOMY-001',
            'enabled' => (bool) config('source-governance.poe_ninja.enabled', false),
            'status' => is_string($status) ? $status : 'not_observed',
            'last_completed_at' => $query->value('completed_at'),
            'failure_code' => $query->value('failure_code'),
            'notice' => 'Market observations are contextual evidence, never invented prices.',
        ];
    }

    /** @return array<string, mixed> */
    private function regressions(): array
    {
        $path = storage_path('app/evaluations/fast-latest.json');
        if (! File::exists($path)) {
            return ['status' => 'not_observed', 'failures' => null, 'generated_at' => null];
        }

        try {
            $report = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
            $cases = is_array($report['cases'] ?? null) ? $report['cases'] : [];
            $failures = count(array_filter($cases, static fn (mixed $case): bool => is_array($case) && ($case['passed'] ?? false) !== true));

            return [
                'status' => $failures === 0 && ($report['passed'] ?? false) === true ? 'pass' : 'fail',
                'failures' => $failures,
                'generated_at' => $report['generated_at'] ?? null,
                'scope' => 'Structural evaluation; not staging player acceptance.',
            ];
        } catch (Throwable) {
            return ['status' => 'invalid_report', 'failures' => null, 'generated_at' => null];
        }
    }

    /** @return array<string, int|float|null> */
    private function aiHealth(): array
    {
        $today = DB::table('ai_request_audits')->where('created_at', '>=', now()->startOfDay());
        $calls = (clone $today)->count();

        return [
            'calls_today' => $calls,
            'failures_today' => (clone $today)->where('validation_outcome', '!=', 'valid')->count(),
            'average_latency_ms' => $calls === 0 ? null : round((float) (clone $today)->avg('latency_ms'), 2),
        ];
    }

    /** @return array{sample_size:int,analyses_with_unsupported:int,rate_percent:float|null} */
    private function unsupportedRate(GameEdition $edition): array
    {
        $rows = DB::table('analyses')
            ->where('game_edition', $edition->value)
            ->where('state', 'completed')
            ->whereNotNull('output_snapshot_encrypted')
            ->latest('updated_at')
            ->limit(100)
            ->get(['output_snapshot_encrypted']);
        $withUnsupported = 0;
        $readable = 0;

        foreach ($rows as $row) {
            try {
                $payload = json_decode(Crypt::decryptString((string) $row->output_snapshot_encrypted), true, flags: JSON_THROW_ON_ERROR);
                $unsupported = $payload['analysis_result']['unsupported_data'] ?? null;
                if (! is_array($unsupported)) {
                    continue;
                }
                $readable++;
                if ($unsupported !== []) {
                    $withUnsupported++;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return [
            'sample_size' => $readable,
            'analyses_with_unsupported' => $withUnsupported,
            'rate_percent' => $readable === 0 ? null : round(($withUnsupported / $readable) * 100, 2),
        ];
    }

    /** @return array{available:int,expected:int|null,coverage_percent:float|null,status:string} */
    private function ruleCoverage(mixed $canonicalPayload): array
    {
        try {
            $payload = is_string($canonicalPayload) ? json_decode($canonicalPayload, true, flags: JSON_THROW_ON_ERROR) : [];
            $rules = $payload['deterministic_analysis']['rule_codes'] ?? [];
            $available = is_array($rules) ? count($rules) : 0;

            return ['available' => $available, 'expected' => null, 'coverage_percent' => null, 'status' => $available > 0 ? 'unknown_completeness' : 'missing'];
        } catch (Throwable) {
            return ['available' => 0, 'expected' => null, 'coverage_percent' => null, 'status' => 'invalid'];
        }
    }

    /** @param list<array<string, mixed>> $coverage
     * @return array<string, mixed>|null
     */
    private function coverageCategory(array $coverage, string $category): ?array
    {
        foreach ($coverage as $entry) {
            if (($entry['category'] ?? null) === $category) {
                return $entry;
            }
        }

        return null;
    }

    /** @return array{key:string,label:string,status:string,evidence:string,critical:bool} */
    private function gate(string $key, string $label, bool $passed, string $evidence, bool $critical, ?string $failedStatus = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $passed ? 'PASS' : ($failedStatus ?? 'FAIL'),
            'evidence' => $evidence,
            'critical' => $critical,
        ];
    }

    private function elapsedMilliseconds(mixed $start, mixed $end): ?int
    {
        if (! is_string($start) || ! is_string($end)) {
            return null;
        }

        try {
            return max(0, (int) round(CarbonImmutable::parse($start)->diffInMilliseconds(CarbonImmutable::parse($end))));
        } catch (Throwable) {
            return null;
        }
    }

    private function isProductionEngine(object $engine): bool
    {
        return $engine::class === ProductionPoe1DeterministicAnalysisEngine::class;
    }

    private function evidenceId(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{7,127}$/D', $value) === 1
            ? $value
            : null;
    }
}
