<?php

namespace App\Modules\Analysis\Infrastructure;

use Illuminate\Support\Facades\DB;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\GameVersion;
use Lootwright\Domain\Rulesets\Ports\RulesetResolver;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\Shared\Value\Locale;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisEngine;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisRuleset;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1DeterministicAnalysisEngine as CoreEngine;
use RuntimeException;
use Throwable;

final readonly class ProductionPoe1DeterministicAnalysisEngine implements DeterministicAnalysisEngine
{
    public function __construct(
        private RulesetResolver $rulesets,
        private CoreEngine $engine,
    ) {}

    public function resolve(AnalysisRecord $analysis, ArtifactRecord $artifact): ResolvedAnalysisContext
    {
        if ($analysis->edition !== GameEdition::Poe1 || $artifact->edition !== GameEdition::Poe1 || $artifact->adapterKey !== 'pob1') {
            throw new TerminalWorkflowFailure('poe1_analysis_required', 'The production deterministic engine accepts only normalized PoE1 PoB artifacts.');
        }
        $identity = $this->resolveIdentity($artifact);

        return new ResolvedAnalysisContext(
            $artifact->adapterKey,
            $artifact->parserVersion ?? '',
            $identity->id->value,
            $identity->version->value,
            $identity->checksumSha256,
            $identity->provenance->sourceId,
            $identity->provenance->sourceVersion->value,
            $identity->patch->value,
            $identity->league?->value,
        );
    }

    public function run(AnalysisRecord $analysis, ArtifactRecord $artifact, ResolvedAnalysisContext $context): DeterministicAnalysisSnapshot
    {
        try {
            $identity = $this->resolveIdentity($artifact);
            if ($identity->id->value !== $context->rulesetId || $identity->version->value !== $context->rulesetVersion || $identity->checksumSha256 !== $context->rulesetChecksumSha256) {
                throw new TerminalWorkflowFailure('ruleset_changed_after_resolution', 'The active ruleset identity changed before deterministic execution.');
            }
            [$analysisRules, $knownNodes, $snapshotProvenance] = $this->loadAndVerifyRuleset($identity);
            $build = $this->hydrateBuild($artifact->normalizedSnapshot ?? '');
            $analysisId = AnalysisId::from(GameEdition::Poe1, $analysis->id);
            if ($analysisId->isFailure() || ! $analysisId->value() instanceof AnalysisId) {
                throw new TerminalWorkflowFailure('invalid_analysis_identity', 'The analysis identity is not a canonical PoE1 UUIDv7.');
            }
            $provenance = [
                'input' => ['source_code' => 'USER-POB-001', 'normalized_checksum_sha256' => $artifact->normalizedHashSha256],
                'analysis_manifest' => [
                    'engine_version' => $analysisRules->engineVersion,
                    'checksum_sha256' => hash('sha256', CanonicalJson::encode($analysisRules)),
                ],
                'ruleset' => ['id' => $identity->id->value, 'version' => $identity->version->value, 'checksum_sha256' => $identity->checksumSha256],
                'passive_tree_snapshot' => $snapshotProvenance,
            ];
            $gameRuleset = new GameRuleset(
                $identity,
                new GameVersion(GameEdition::Poe1, $identity->patch),
                DatasetClassification::ApprovedImport,
                ProvenanceStatus::Approved,
                RulesetCompatibilityStatus::Compatible,
            );
            $locale = Locale::from('en-US');
            if ($locale->isFailure()) {
                throw new RuntimeException('The deterministic fallback locale is invalid.');
            }
            $intent = BuildIntent::unspecified(GameEdition::Poe1, $locale->value());
            $result = (new Poe1AnalysisEngine($this->engine, $analysisRules, $knownNodes, sourceProvenance: $provenance))
                ->analyzeFor($analysisId->value(), $build, $intent, $gameRuleset);
            $findings = $result->findings;
            $input = CanonicalJson::encode([
                'analysis_parameters_hash_sha256' => $analysis->parametersHashSha256,
                'build' => $this->safeBuildProjection($build),
                'normalized_artifact_hash_sha256' => $artifact->normalizedHashSha256,
                'ruleset' => $provenance['ruleset'],
            ]);
            $output = CanonicalJson::encode([
                'analysis_result' => $result,
                'engine_version' => $result->engineVersion,
                'findings' => $result->findings,
                'recommendations' => [],
                'manual_trade_recipes' => [],
            ]);

            return new DeterministicAnalysisSnapshot(
                'pob1',
                $artifact->parserVersion ?? '',
                $context->rulesetId,
                $context->rulesetVersion,
                $context->rulesetChecksumSha256,
                $input,
                hash('sha256', $input),
                $output,
                hash('sha256', $output),
                $findings,
                [],
                [],
            );
        } catch (TerminalWorkflowFailure $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new TerminalWorkflowFailure('deterministic_analysis_failed_closed', 'The deterministic PoE1 analysis input or ruleset failed validation.');
        }
    }

    private function resolveIdentity(ArtifactRecord $artifact): RulesetIdentity
    {
        if ($artifact->patchVersion === null || $artifact->parserVersion === null) {
            throw new TerminalWorkflowFailure('exact_ruleset_unavailable', 'Exact patch and parser versions are required for deterministic analysis.');
        }
        $patch = PatchVersion::from(GameEdition::Poe1, $artifact->patchVersion);
        $parser = ParserVersion::from(GameEdition::Poe1, $artifact->parserVersion);
        $league = $artifact->league === null ? null : LeagueId::from(GameEdition::Poe1, $artifact->league);
        if ($patch->isFailure() || $parser->isFailure() || ($league !== null && $league->isFailure())) {
            throw new TerminalWorkflowFailure('exact_ruleset_unavailable', 'The normalized patch, league, or parser identity is invalid.');
        }
        $resolved = $this->rulesets->resolve(GameEdition::Poe1, $patch->value(), $league?->value(), $parser->value());
        if ($resolved->isFailure() || ! $resolved->value() instanceof RulesetIdentity) {
            throw new TerminalWorkflowFailure('exact_ruleset_unavailable', 'No approved immutable ruleset exactly matches the normalized PoE1 build.');
        }

        return $resolved->value();
    }

    /** @return array{Poe1AnalysisRuleset, array<string, true>, array<string, string>} */
    private function loadAndVerifyRuleset(RulesetIdentity $identity): array
    {
        $ruleset = DB::table('ruleset_versions')->where('id', $identity->id->value)->where('status', 'published')->first();
        if ($ruleset === null) {
            throw new RuntimeException('Missing published ruleset.');
        }
        $rulesetPayload = $this->json($ruleset->canonical_payload ?? null);
        if (! hash_equals($identity->checksumSha256, hash('sha256', CanonicalJson::encode($rulesetPayload)))) {
            throw new RuntimeException('Ruleset checksum mismatch.');
        }
        $manifest = $rulesetPayload['deterministic_analysis'] ?? null;
        if (! is_array($manifest) || array_is_list($manifest)) {
            throw new RuntimeException('The versioned deterministic analysis manifest is absent.');
        }
        $analysisRules = Poe1AnalysisRuleset::fromPublishedPayload($manifest);
        $snapshot = DB::table('ruleset_source_snapshots as links')
            ->join('source_snapshots as snapshots', 'snapshots.id', '=', 'links.source_snapshot_id')
            ->join('policy_data_source_versions as versions', 'versions.id', '=', 'snapshots.source_version_id')
            ->where('links.ruleset_version_id', $identity->id->value)
            ->where('snapshots.source_code', 'GGG-POE1-SKILLTREE-001')
            ->where('snapshots.status', 'valid')
            ->first(['snapshots.id', 'snapshots.source_code', 'snapshots.checksum_sha256', 'snapshots.normalized_payload', 'snapshots.upstream_revision', 'versions.version as source_version']);
        if ($snapshot === null) {
            throw new RuntimeException('Missing active GGG passive-tree snapshot.');
        }
        $payload = $this->json($snapshot->normalized_payload ?? null);
        $checksum = (string) $snapshot->checksum_sha256;
        if (! hash_equals($checksum, hash('sha256', CanonicalJson::encode($payload)))) {
            throw new RuntimeException('Passive-tree snapshot checksum mismatch.');
        }
        $nodes = $payload['passive_tree']['nodes'] ?? null;
        if (! is_array($nodes) || ! array_is_list($nodes)) {
            throw new RuntimeException('Passive-tree nodes are absent.');
        }
        $known = [];
        foreach ($nodes as $node) {
            if (! is_array($node) || ! is_string($node['id'] ?? null)) {
                throw new RuntimeException('Passive-tree node is invalid.');
            }
            $known[$node['id']] = true;
        }

        return [$analysisRules, $known, [
            'source_code' => (string) $snapshot->source_code,
            'source_version' => (string) $snapshot->source_version,
            'upstream_revision' => (string) $snapshot->upstream_revision,
            'checksum_sha256' => $checksum,
        ]];
    }

    private function hydrateBuild(string $snapshot): CanonicalImportedBuild
    {
        $document = $this->json($snapshot);
        $build = $document['canonical_build'] ?? null;
        if (! is_array($build) || ($build['edition'] ?? null) !== 'poe1') {
            throw new RuntimeException('Normalized PoE1 build is absent.');
        }

        return new CanonicalImportedBuild(
            GameEdition::Poe1,
            is_string($build['build_version'] ?? null) ? $build['build_version'] : null,
            is_int($build['character_level'] ?? null) ? $build['character_level'] : null,
            is_string($build['character_class_id'] ?? null) ? $build['character_class_id'] : null,
            is_string($build['ascendancy_id'] ?? null) ? $build['ascendancy_id'] : null,
            is_array($build['choices'] ?? null) ? $build['choices'] : [],
            is_array($build['passive_node_ids'] ?? null) ? array_values($build['passive_node_ids']) : [],
            is_array($build['skills'] ?? null) ? array_values($build['skills']) : [],
            is_array($build['items'] ?? null) ? array_values($build['items']) : [],
            is_array($build['configuration'] ?? null) ? $build['configuration'] : [],
            is_array($build['summary_values'] ?? null) ? $build['summary_values'] : [],
            '',
            false,
        );
    }

    /** @return array<string, mixed> */
    private function safeBuildProjection(CanonicalImportedBuild $build): array
    {
        $items = array_map(static fn (array $item): array => ['id' => $item['id'] ?? null, 'slots' => $item['slots'] ?? []], array_filter($build->items, 'is_array'));

        return [
            'edition' => $build->edition->value,
            'build_version' => $build->buildVersion,
            'character_level' => $build->characterLevel,
            'character_class_id' => $build->characterClassId,
            'ascendancy_id' => $build->ascendancyId,
            'passive_node_ids' => $build->passiveNodeIds,
            'skills' => $build->skills,
            'items' => array_values($items),
            'summary_values' => $build->summaryValues,
        ];
    }

    /** @return array<string, mixed> */
    private function json(mixed $value): array
    {
        $decoded = is_string($value) ? json_decode($value, true, 64, JSON_THROW_ON_ERROR) : $value;
        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('Expected a canonical JSON object.');
        }

        return $decoded;
    }
}
