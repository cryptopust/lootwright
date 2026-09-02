<?php

namespace App\Modules\Analysis\Infrastructure;

use Illuminate\Support\Facades\DB;
use Lootwright\Application\TradePlanning\TradeRecipeBuilder;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\BuildIntake\Intent\ContentGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayerGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayStyle;
use Lootwright\Domain\BuildIntake\Intent\UpgradePriority;
use Lootwright\Domain\PoeCatalog\BuildCatalog;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\ModifierDefinition;
use Lootwright\Domain\PoeCatalog\Identifier\AscendancyId;
use Lootwright\Domain\PoeCatalog\Identifier\CharacterClassId;
use Lootwright\Domain\PoeCatalog\Ports\GameDataRepository;
use Lootwright\Domain\Recommendations\BudgetConstraint;
use Lootwright\Domain\Recommendations\Ports\UpgradePlanner;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Recommendations\RecommendationImpact;
use Lootwright\Domain\Recommendations\UpgradeClassification;
use Lootwright\Domain\Recommendations\UpgradeGraph;
use Lootwright\Domain\Recommendations\UserConstraint;
use Lootwright\Domain\Recommendations\UserConstraints;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\GameVersion;
use Lootwright\Domain\Rulesets\Ports\RulesetResolver;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Game\GameScope;
use Lootwright\Domain\Shared\Game\PlatformRealm;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Identity\BuildId;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\Shared\Value\Budget;
use Lootwright\Domain\Shared\Value\Confidence;
use Lootwright\Domain\Shared\Value\CurrencyCode;
use Lootwright\Domain\Shared\Value\Locale;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Lootwright\Domain\TradePlanning\TradeRecipe;
use Lootwright\Domain\TradePlanning\TradeVocabularyEntry;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisEngine;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisRuleset;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1DeterministicAnalysisEngine as CoreEngine;
use Lootwright\GameAdapters\PoE1\Rulesets\Poe1CanonicalResolver;
use Lootwright\GameAdapters\PoE1\Rulesets\Poe1Ruleset;
use Lootwright\GameAdapters\PoE1\Rulesets\Poe1RulesetLoader;
use Lootwright\GameAdapters\PoE1\TradePlanning\Poe1TradeVocabulary;
use RuntimeException;
use Throwable;

final readonly class ProductionPoe1DeterministicAnalysisEngine implements DeterministicAnalysisEngine
{
    public function __construct(
        private RulesetResolver $rulesets,
        private CoreEngine $engine,
        private UpgradePlanner $planner,
        private TradeRecipeBuilder $tradeRecipeBuilder,
        private GameDataRepository $gameData,
        private Poe1RulesetLoader $rulesetLoader,
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
            [$analysisRules, $knownNodes, $snapshotProvenance, $canonicalRuleset] = $this->loadAndVerifyRuleset($identity);
            $build = $this->hydrateBuild($artifact->normalizedSnapshot ?? '');
            $build = $this->hydrateCanonicalKeystones($build, $canonicalRuleset);
            $this->validateCanonicalBuildReferences($build, $canonicalRuleset);
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
            $intent = $this->intent($analysis, $locale->value());
            $result = (new Poe1AnalysisEngine($this->engine, $analysisRules, $knownNodes, sourceProvenance: $provenance))
                ->analyzeFor($analysisId->value(), $build, $intent, $gameRuleset);
            $findings = $result->findings;
            $plannerStarted = hrtime(true);
            $graphResult = $this->planner->plan($result, $intent, $this->budget($analysis), $this->constraints($analysis, $build));
            $plannerLatencyMs = (int) ceil((hrtime(true) - $plannerStarted) / 1_000_000);
            if ($graphResult->isFailure() || ! $graphResult->value() instanceof UpgradeGraph) {
                throw new RuntimeException('The deterministic upgrade planner failed closed.');
            }
            $graph = $graphResult->value();
            $constraints = $this->constraints($analysis, $build);
            $budget = $this->budget($analysis);
            $recommendations = $this->recommendations($graph, $findings, $analysisId->value(), $intent, $constraints, $budget);
            $recipeStarted = hrtime(true);
            $recipes = $this->recipes($graph, $build, $artifact, $identity, $gameRuleset);
            $recipeLatencyMs = (int) ceil((hrtime(true) - $recipeStarted) / 1_000_000);
            $input = CanonicalJson::encode([
                'analysis_parameters_hash_sha256' => $analysis->parametersHashSha256,
                'build' => $this->safeBuildProjection($build),
                'normalized_artifact_hash_sha256' => $artifact->normalizedHashSha256,
                'ruleset' => $provenance['ruleset'],
            ]);
            $output = CanonicalJson::encode([
                'analysis_result' => $result,
                'build_summary' => $this->safeBuildProjection($build),
                'engine_version' => $result->engineVersion,
                'findings' => $result->findings,
                'recommendations' => $recommendations,
                'manual_trade_recipes' => $recipes,
                'upgrade_graph' => $graph,
                'intent' => $intent,
                'constraints' => [
                    'locked_items' => array_values(array_filter($this->parameters($analysis)['locked_items'] ?? [], 'is_string')),
                ],
                'budget' => $this->parameters($analysis)['budget'] ?? null,
                // Operational timings are recorded by the workflow telemetry;
                // the canonical output remains byte-stable across replays.
                'latencies_ms' => ['planner' => 0, 'trade_recipe' => 0],
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
                $recommendations,
                $recipes,
            );
        } catch (TerminalWorkflowFailure $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new TerminalWorkflowFailure('deterministic_analysis_failed_closed', 'The deterministic PoE1 analysis input or ruleset failed validation.');
        }
    }

    private function intent(AnalysisRecord $analysis, Locale $locale): BuildIntent
    {
        $parameters = $this->parameters($analysis);
        $selection = is_array($parameters['selection'] ?? null) ? $parameters['selection'] : [];
        $goals = array_values(array_filter($parameters['goals'] ?? [], 'is_string'));
        $description = implode(' ', $goals) ?: 'Review the imported PoE1 build for the selected content.';
        $content = (string) ($selection['content_goal'] ?? 'progression');
        if (! in_array($content, ['mapping', 'bossing', 'delve', 'simulacrum', 'sanctum', 'progression'], true)) {
            $lower = strtolower($description);
            $content = str_contains($lower, 'boss') ? 'bossing' : (str_contains($lower, 'delve') ? 'delve' : 'progression');
        }
        $style = str_contains(strtolower($description), 'defen') ? 'tank' : 'balanced';
        $goal = PlayerGoal::create(
            GameEdition::Poe1,
            $description,
            ContentGoal::from(GameEdition::Poe1, $content)->value(),
            PlayStyle::from(GameEdition::Poe1, $style)->value(),
        )->value();
        $intent = BuildIntent::create($goal, $locale, Confidence::fromBasisPoints(10_000)->value(), []);
        if ($intent->isFailure() || ! $intent->value() instanceof BuildIntent) {
            throw new RuntimeException('PoE1 player intent could not be normalized.');
        }

        return $intent->value();
    }

    private function budget(AnalysisRecord $analysis): BudgetConstraint
    {
        $parameters = $this->parameters($analysis);
        $budget = $parameters['budget'] ?? null;
        if (! is_array($budget) || ! is_string($budget['currency'] ?? null) || ! is_string($budget['amount'] ?? null)) {
            return BudgetConstraint::unknown();
        }
        $currency = CurrencyCode::from($budget['currency']);
        if ($currency->isFailure()) {
            return BudgetConstraint::unknown();
        }
        $value = Budget::fromDecimal($currency->value(), $budget['amount']);

        return $value->isFailure() ? BudgetConstraint::unknown() : BudgetConstraint::limitedTo($value->value());
    }

    private function constraints(AnalysisRecord $analysis, CanonicalImportedBuild $build): UserConstraints
    {
        $parameters = $this->parameters($analysis);
        $text = strtolower(implode(' ', array_values(array_filter($parameters['goals'] ?? [], 'is_string'))));
        $values = [];
        foreach ($this->parameters($analysis)['locked_items'] ?? [] as $lockedItem) {
            if (is_string($lockedItem) && $lockedItem !== '') {
                $values[] = UserConstraint::keepItem($lockedItem);
            }
        }
        foreach ($build->items as $item) {
            $name = strtolower((string) ($item['name'] ?? $item['id'] ?? ''));
            if (str_contains($name, 'mageblood') && (str_contains($text, 'keep') || str_contains($text, 'without replacing'))) {
                $values[] = UserConstraint::keepItem('mageblood');
            }
            // PoB normalizes equipment placement as a `slots` list.  Older
            // imports may expose a scalar `slot`, so accept both shapes but
            // never infer a weapon from an item name alone.
            $slots = $item['slots'] ?? ($item['slot'] ?? []);
            $slots = is_array($slots) ? $slots : [$slots];
            $slots = array_map(static fn (mixed $slot): string => strtolower(trim((string) $slot)), $slots);
            if (array_filter($slots, static fn (string $slot): bool => str_contains($slot, 'weapon')) !== []
                && str_contains($text, 'without replacing')
            ) {
                $values[] = UserConstraint::keepItem((string) ($item['id'] ?? 'main_weapon'));
            }
        }

        $unique = [];
        foreach ($values as $constraint) {
            $unique[$constraint->key] = $constraint;
        }

        return new UserConstraints(array_values($unique));
    }

    /**
     * @param  list<Finding>  $findings
     * @return list<Recommendation>
     */
    private function recommendations(UpgradeGraph $graph, array $findings, AnalysisId $analysisId, BuildIntent $intent, UserConstraints $constraints, BudgetConstraint $budget): array
    {
        $byId = [];
        foreach ($findings as $finding) {
            $byId[$finding->findingId] = $finding;
        }
        $recommendations = [];
        foreach ($graph->ordered() as $candidate) {
            $owned = array_values(array_filter(array_map(static fn (string $id) => $byId[$id] ?? null, $candidate->affectedFindings), static fn (mixed $finding): bool => $finding instanceof Finding));
            if ($owned === []) {
                continue;
            }
            $priority = match ($candidate->classification) {
                UpgradeClassification::Mandatory => UpgradePriority::Critical,
                UpgradeClassification::Structural, UpgradeClassification::HighImpact => UpgradePriority::High,
                default => UpgradePriority::Medium,
            };
            $impact = RecommendationImpact::create(['deterministic_score' => min(10_000, $candidate->score)]);
            $decisionTrace = [
                'user_goal' => $intent->goal->description,
                'finding' => array_map(static fn ($finding): string => $finding->findingId, $owned),
                'evidence' => array_map(static fn ($finding): array => $finding->evidence, $owned),
                'rule' => array_map(static fn ($finding): string => $finding->ruleId, $owned),
                'upgrade_candidate' => $candidate,
                'constraints' => ['user_constraints' => $constraints, 'budget' => $budget],
                'market_evidence' => ['status' => $candidate->budgetUncertainty->value],
                'recommendation' => ['rank_score' => $candidate->score, 'ordering_reason' => $graph->orderingReasons()[$candidate->id] ?? null],
            ];
            $recommendation = Recommendation::create(
                GameEdition::Poe1,
                $analysisId,
                'recommendation.'.str_replace(':', '.', $candidate->id),
                $priority,
                $impact->value(),
                $owned,
                [],
                $owned[0]->trace,
                $decisionTrace,
            );
            if ($recommendation->isSuccess() && $recommendation->value() instanceof Recommendation) {
                $recommendations[] = $recommendation->value();
            }
        }

        return $recommendations;
    }

    /** @return list<TradeRecipe> */
    private function recipes(UpgradeGraph $graph, CanonicalImportedBuild $build, ArtifactRecord $artifact, RulesetIdentity $identity, GameRuleset $ruleset): array
    {
        $class = is_string($build->characterClassId) ? CharacterClassId::from(GameEdition::Poe1, $build->characterClassId) : null;
        if ($class === null || $class->isFailure()) {
            return $this->unsupportedRecipes($graph, $identity, 'canonical character class is unavailable');
        }
        $ascendancy = is_string($build->ascendancyId) ? AscendancyId::from(GameEdition::Poe1, $build->ascendancyId) : null;
        if ($ascendancy !== null && $ascendancy->isFailure()) {
            $ascendancy = null;
        }
        $catalog = BuildCatalog::fromCanonical(GameEdition::Poe1, $class->value(), $ascendancy?->value());
        $scope = GameScope::create(GameEdition::Poe1, PlatformRealm::Pc);
        $buildId = BuildId::from(GameEdition::Poe1, $artifact->id);
        $patch = PatchVersion::from(GameEdition::Poe1, $identity->patch->value);
        $parser = ParserVersion::from(GameEdition::Poe1, $artifact->parserVersion ?? $identity->parserVersion->value);
        $locale = Locale::from('en-US');
        if ($catalog->isFailure() || $scope->isFailure() || $buildId->isFailure() || $patch->isFailure() || $parser->isFailure() || $locale->isFailure()) {
            return $this->unsupportedRecipes($graph, $identity, 'canonical build snapshot context is unavailable');
        }
        $snapshot = BuildSnapshot::create(
            $buildId->value(),
            $scope->value(),
            $patch->value(),
            $identity->league,
            $parser->value(),
            $locale->value(),
            $catalog->value(),
            $artifact->normalizedHashSha256 ?? hash('sha256', $artifact->normalizedSnapshot ?? ''),
        );
        if ($snapshot->isFailure()) {
            return $this->unsupportedRecipes($graph, $identity, 'canonical build snapshot could not be constructed');
        }
        $vocabulary = $this->vocabulary($identity);
        $recipes = [];
        foreach ($graph->ordered() as $candidate) {
            try {
                $recipes[] = $this->tradeRecipeBuilder->build($candidate, $snapshot->value(), $ruleset, $vocabulary);
            } catch (Throwable) {
                // Unknown vocabulary is a typed unsupported result at the
                // recommendation layer, never a fabricated manual filter.
                $recipes[] = new TradeRecipe(
                    GameEdition::Poe1,
                    new RulesetReference(GameEdition::Poe1, $identity->id->value, $identity->version->value, $identity->checksumSha256),
                    $candidate->targetSlot ?? 'unsupported.'.str_replace(':', '.', $candidate->id),
                    null,
                    [],
                    null,
                    null,
                    null,
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                    '',
                    '',
                    'Unsupported manual recipe: exact approved vocabulary or compatible build context is unavailable.',
                    ['ruleset' => $identity],
                    [['candidate' => $candidate->id, 'reason' => 'exact approved vocabulary or build context unavailable']],
                );
            }
        }

        return $recipes;
    }

    /** @return list<TradeRecipe> */
    private function unsupportedRecipes(UpgradeGraph $graph, RulesetIdentity $identity, string $reason): array
    {
        $ruleset = new RulesetReference(GameEdition::Poe1, $identity->id->value, $identity->version->value, $identity->checksumSha256);

        return array_map(static fn ($candidate): TradeRecipe => new TradeRecipe(
            GameEdition::Poe1,
            $ruleset,
            $candidate->targetSlot ?? 'unsupported.'.str_replace(':', '.', $candidate->id),
            null,
            [], null, null, null, [], [], [], [], [], [], '', '',
            'Unsupported manual recipe: '.$reason,
            ['ruleset' => $identity],
            [['candidate' => $candidate->id, 'reason' => $reason]],
        ), $graph->ordered());
    }

    private function vocabulary(RulesetIdentity $identity): Poe1TradeVocabulary
    {
        $entities = $this->gameData->listForRuleset(GameEdition::Poe1, $identity->id->value, CanonicalEntityType::ModifierDefinition);
        $entries = [];
        foreach ($entities as $entity) {
            if (! $entity instanceof ModifierDefinition || $entity->displayName === null) {
                continue;
            }
            $entries[] = new TradeVocabularyEntry(
                GameEdition::Poe1,
                $entity->externalId,
                $entity->displayName,
                new SourceProvenanceReference(
                    GameEdition::Poe1,
                    $entity->provenance->sourceCode,
                    $entity->provenance->sourceVersion,
                    $entity->provenance->checksumSha256,
                    $entity->provenance->snapshotId,
                    $entity->provenance->importedAt,
                ),
            );
        }

        return new Poe1TradeVocabulary($identity, $entries, [
            'helmet' => 'Armour > Helmets',
            'ring' => 'Rings',
            'amulet' => 'Amulets',
            'gloves' => 'Armour > Gloves',
            'boots' => 'Armour > Boots',
            'belt' => 'Belts',
        ]);
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

    /** @return array{Poe1AnalysisRuleset, array<string, true>, array<string, string>, Poe1Ruleset} */
    private function loadAndVerifyRuleset(RulesetIdentity $identity): array
    {
        $ruleset = DB::table('ruleset_versions')->where('id', $identity->id->value)->where('status', 'published')->first();
        if ($ruleset === null) {
            throw new RuntimeException('Missing published ruleset.');
        }
        if ((string) ($ruleset->game_edition ?? '') !== GameEdition::Poe1->value
            || (string) ($ruleset->version ?? '') !== $identity->version->value
            || (string) ($ruleset->patch ?? '') !== $identity->patch->value
            || (string) ($ruleset->parser_version ?? '') !== $identity->parserVersion->value
            || ! hash_equals($identity->checksumSha256, (string) ($ruleset->checksum_sha256 ?? ''))
        ) {
            throw new RuntimeException('Published PoE1 ruleset metadata does not match its immutable identity.');
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

        $canonicalRuleset = $this->rulesetLoader->load($identity);

        return [$analysisRules, $known, [
            'source_code' => (string) $snapshot->source_code,
            'source_version' => (string) $snapshot->source_version,
            'upstream_revision' => (string) $snapshot->upstream_revision,
            'checksum_sha256' => $checksum,
        ], $canonicalRuleset];
    }

    private function validateCanonicalBuildReferences(CanonicalImportedBuild $build, Poe1Ruleset $ruleset): void
    {
        $resolver = new Poe1CanonicalResolver($ruleset);
        $class = $build->characterClassId === null ? null : $resolver->characterClass($build->characterClassId);
        if ($build->characterClassId !== null && $class === null) {
            throw new TerminalWorkflowFailure('unknown_canonical_class', 'The PoE1 character class is not present in the active canonical ruleset.');
        }
        $ascendancy = $build->ascendancyId === null ? null : $resolver->ascendancy($build->ascendancyId);
        if ($build->ascendancyId !== null && $ascendancy === null) {
            throw new TerminalWorkflowFailure('unknown_canonical_ascendancy', 'The PoE1 ascendancy is not present in the active canonical ruleset.');
        }
        if ($class !== null && $ascendancy !== null && $ascendancy->characterClassExternalId !== $class->externalId) {
            throw new TerminalWorkflowFailure('canonical_ascendancy_class_mismatch', 'The PoE1 ascendancy does not belong to the resolved canonical character class.');
        }
    }

    /**
     * PoB exports encode allocated keystones in the passive-node list. Resolve
     * those numeric IDs against the active immutable ruleset before analysis so
     * mechanic-aware rules (CI/RT) receive canonical identities. Unknown IDs
     * remain untouched and continue through the normal fail-closed diagnostics.
     */
    private function hydrateCanonicalKeystones(CanonicalImportedBuild $build, Poe1Ruleset $ruleset): CanonicalImportedBuild
    {
        $resolver = new Poe1CanonicalResolver($ruleset);
        $keystones = $build->keystones;
        foreach ($build->passiveNodeIds as $nodeId) {
            $entity = $resolver->resolve(CanonicalEntityType::Keystone, $nodeId);
            if ($entity !== null) {
                $keystones[] = $entity->externalId;
            }
        }
        $keystones = array_values(array_unique(array_filter($keystones, 'is_string')));
        sort($keystones, SORT_STRING);

        return new CanonicalImportedBuild(
            $build->edition,
            $build->buildVersion,
            $build->characterLevel,
            $build->characterClassId,
            $build->ascendancyId,
            $build->choices,
            $build->passiveNodeIds,
            $build->skills,
            $build->items,
            $build->configuration,
            $build->summaryValues,
            $build->notes,
            $build->beta,
            $build->attributes,
            $build->life,
            $build->energyShield,
            $build->mana,
            $build->armour,
            $build->evasion,
            $build->resistances,
            $build->supports,
            $build->auras,
            $build->itemModifiers,
            $keystones,
            $build->jewels,
            $build->clusters,
            $build->propertySupport,
            $build->unsupportedFields,
            $build->warnings,
            $build->sourceMetadata,
        );
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
            is_string($build['notes_untrusted_text'] ?? null) ? $build['notes_untrusted_text'] : '',
            ($build['beta'] ?? false) === true,
            is_array($build['attributes'] ?? null) ? $build['attributes'] : [],
            is_int($build['life'] ?? null) || is_string($build['life'] ?? null) ? $build['life'] : null,
            is_int($build['energy_shield'] ?? null) || is_string($build['energy_shield'] ?? null) ? $build['energy_shield'] : null,
            is_int($build['mana'] ?? null) || is_string($build['mana'] ?? null) ? $build['mana'] : null,
            is_int($build['armour'] ?? null) || is_string($build['armour'] ?? null) ? $build['armour'] : null,
            is_int($build['evasion'] ?? null) || is_string($build['evasion'] ?? null) ? $build['evasion'] : null,
            is_array($build['resistances'] ?? null) ? $build['resistances'] : [],
            $this->listOfArrays($build['supports'] ?? null),
            $this->listOfArrays($build['auras'] ?? null),
            $this->listOfArrays($build['item_modifiers'] ?? null),
            is_array($build['keystones'] ?? null) ? array_values(array_filter($build['keystones'], 'is_string')) : [],
            $this->listOfArrays($build['jewels'] ?? null),
            $this->listOfArrays($build['clusters'] ?? null),
            [],
            [],
            [],
        );
    }

    /** @return list<array<string, mixed>> */
    private function listOfArrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $entry): bool => is_array($entry)));
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
            'attributes' => $build->attributes,
            'life' => $build->life,
            'energy_shield' => $build->energyShield,
            'mana' => $build->mana,
            'armour' => $build->armour,
            'evasion' => $build->evasion,
            'resistances' => $build->resistances,
            'keystones' => $build->keystones,
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

    /** @return array<string,mixed> */
    private function parameters(AnalysisRecord $analysis): array
    {
        $decoded = json_decode($analysis->parametersSnapshot, true, 64, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }
}
