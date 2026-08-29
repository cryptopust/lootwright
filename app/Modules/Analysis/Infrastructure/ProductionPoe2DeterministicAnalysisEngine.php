<?php

namespace App\Modules\Analysis\Infrastructure;

use Illuminate\Support\Facades\DB;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\BuildIntake\Import\BuildInputType;
use Lootwright\Domain\BuildIntake\Import\BuildSourceMetadata;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Import\ImportWarning;
use Lootwright\Domain\BuildIntake\Import\PropertySupportStatus;
use Lootwright\Domain\BuildIntake\Import\UnsupportedFeature;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\BuildIntake\Intent\ContentGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayerGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayStyle;
use Lootwright\Domain\Recommendations\BudgetConstraint;
use Lootwright\Domain\Recommendations\Ports\UpgradePlanner;
use Lootwright\Domain\Recommendations\UpgradeGraph;
use Lootwright\Domain\Recommendations\UserConstraints;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\GameVersion;
use Lootwright\Domain\Rulesets\Ports\RulesetResolver;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\Shared\Value\Confidence;
use Lootwright\Domain\Shared\Value\Locale;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Lootwright\GameAdapters\PoE2\Analysis\Poe2AnalysisEngine;
use Lootwright\GameAdapters\PoE2\Analysis\Poe2AnalysisRuleset;
use Lootwright\GameAdapters\PoE2\Rulesets\Poe2CanonicalResolver;
use Lootwright\GameAdapters\PoE2\Rulesets\Poe2ModifierResolver;
use Lootwright\GameAdapters\PoE2\Rulesets\Poe2PassiveResolver;
use Lootwright\GameAdapters\PoE2\Rulesets\Poe2Ruleset;
use Lootwright\GameAdapters\PoE2\Rulesets\Poe2RulesetLoader;
use Lootwright\GameAdapters\PoE2\Rulesets\Poe2SkillResolver;
use RuntimeException;

/** Production PoE2 binding. It activates only for an approved immutable PoE2 ruleset. */
final readonly class ProductionPoe2DeterministicAnalysisEngine implements DeterministicAnalysisEngine
{
    public const ENGINE_VERSION = '1.0.0';

    public function __construct(private RulesetResolver $rulesets, private Poe2RulesetLoader $loader, private UpgradePlanner $planner) {}

    public function resolve(AnalysisRecord $analysis, ArtifactRecord $artifact): ResolvedAnalysisContext
    {
        if ($analysis->edition !== GameEdition::Poe2 || $artifact->edition !== GameEdition::Poe2 || $artifact->adapterKey !== 'pob2-beta') {
            throw new TerminalWorkflowFailure('poe2_analysis_required', 'The production deterministic engine accepts only normalized PoE2 artifacts.');
        }
        if ($artifact->patchVersion === null || $artifact->parserVersion === null) {
            throw new TerminalWorkflowFailure('exact_ruleset_unavailable', 'Exact PoE2 patch and parser versions are required.');
        }
        $patch = PatchVersion::from(GameEdition::Poe2, $artifact->patchVersion);
        $parser = ParserVersion::from(GameEdition::Poe2, $artifact->parserVersion);
        $league = $artifact->league === null ? null : LeagueId::from(GameEdition::Poe2, $artifact->league);
        if ($patch->isFailure() || $parser->isFailure() || ($league !== null && $league->isFailure())) {
            throw new TerminalWorkflowFailure('exact_ruleset_unavailable', 'The normalized PoE2 ruleset identity is invalid.');
        }
        $resolved = $this->rulesets->resolve(GameEdition::Poe2, $patch->value(), $league?->value(), $parser->value());
        if ($resolved->isFailure()) {
            throw new TerminalWorkflowFailure('exact_ruleset_unavailable', 'No approved immutable PoE2 ruleset matches the build.');
        }
        $identity = $resolved->value();

        return new ResolvedAnalysisContext('pob2-beta', $artifact->parserVersion, $identity->id->value, $identity->version->value, $identity->checksumSha256, $identity->provenance->sourceId, $identity->provenance->sourceVersion->value, $identity->patch->value, $identity->league?->value);
    }

    public function run(AnalysisRecord $analysis, ArtifactRecord $artifact, ResolvedAnalysisContext $context): DeterministicAnalysisSnapshot
    {
        $patch = PatchVersion::from(GameEdition::Poe2, (string) $context->patchVersion);
        $parser = ParserVersion::from(GameEdition::Poe2, $context->parserVersion);
        $league = $context->league === null ? null : LeagueId::from(GameEdition::Poe2, $context->league);
        if ($patch->isFailure() || $parser->isFailure() || ($league !== null && $league->isFailure())) {
            throw new TerminalWorkflowFailure('exact_ruleset_unavailable', 'The PoE2 execution context has an invalid ruleset identity.');
        }
        $identityResult = $this->rulesets->resolve(GameEdition::Poe2, $patch->value(), $league?->value(), $parser->value());
        if ($identityResult->isFailure()) {
            throw new TerminalWorkflowFailure('exact_ruleset_unavailable', 'PoE2 ruleset resolution failed.');
        }
        $identity = $identityResult->value();
        if ($identity->id->value !== $context->rulesetId
            || $identity->version->value !== $context->rulesetVersion
            || $identity->checksumSha256 !== $context->rulesetChecksumSha256) {
            throw new TerminalWorkflowFailure('ruleset_changed_after_resolution', 'The active PoE2 ruleset identity changed before deterministic execution.');
        }
        $row = DB::table('ruleset_versions')->where('id', $identity->id->value)->where('status', 'published')->first(['canonical_payload']);
        if ($row === null) {
            throw new RuntimeException('Missing published PoE2 ruleset.');
        }
        $payload = json_decode((string) $row->canonical_payload, true, 64, JSON_THROW_ON_ERROR);
        if (! is_array($payload) || ! hash_equals($identity->checksumSha256, hash('sha256', CanonicalJson::encode($payload)))) {
            throw new RuntimeException('PoE2 ruleset checksum mismatch.');
        }
        $manifest = $payload['deterministic_analysis'] ?? null;
        if (! is_array($manifest)) {
            throw new RuntimeException('PoE2 deterministic analysis manifest is absent.');
        }
        $configuration = Poe2AnalysisRuleset::fromPublishedPayload($manifest);
        $canonicalRuleset = $this->loader->load($identity);
        $document = json_decode((string) $artifact->normalizedSnapshot, true, 64, JSON_THROW_ON_ERROR);
        $buildPayload = is_array($document) ? ($document['canonical_build'] ?? null) : null;
        if (! is_array($buildPayload) || ($buildPayload['edition'] ?? null) !== 'poe2') {
            throw new TerminalWorkflowFailure('invalid_poe2_build', 'Normalized PoE2 build is absent.');
        }
        $build = $this->hydrateBuild($buildPayload);
        $this->validateCanonicalBuild($build, $canonicalRuleset);
        $idResult = AnalysisId::from(GameEdition::Poe2, $analysis->id);
        if ($idResult->isFailure() || ! $idResult->value() instanceof AnalysisId) {
            throw new TerminalWorkflowFailure('invalid_analysis_identity', 'The analysis identity is not a canonical PoE2 UUIDv7.');
        }
        $id = $idResult->value();
        $ruleset = new GameRuleset($identity, new GameVersion(GameEdition::Poe2, $identity->patch), DatasetClassification::ApprovedImport, ProvenanceStatus::Approved, RulesetCompatibilityStatus::Compatible);
        $contentResult = ContentGoal::from(GameEdition::Poe2, 'progression');
        $styleResult = PlayStyle::from(GameEdition::Poe2, 'balanced');
        if ($contentResult->isFailure() || $styleResult->isFailure()) {
            throw new RuntimeException('PoE2 goal values could not be normalized.');
        }
        $content = $contentResult->value();
        $style = $styleResult->value();
        $goalResult = PlayerGoal::create(GameEdition::Poe2, 'Review PoE2 build', $content, $style);
        if ($goalResult->isFailure()) {
            throw new RuntimeException('PoE2 goal could not be normalized.');
        }
        $localeResult = Locale::from('en-US');
        $confidenceResult = Confidence::fromBasisPoints(10000);
        if ($localeResult->isFailure() || $confidenceResult->isFailure()) {
            throw new RuntimeException('PoE2 intent metadata could not be normalized.');
        }
        $intentResult = BuildIntent::create($goalResult->value(), $localeResult->value(), $confidenceResult->value(), []);
        if ($intentResult->isFailure()) {
            throw new RuntimeException('PoE2 intent could not be normalized.');
        }
        $intent = $intentResult->value();
        $analysisResult = (new Poe2AnalysisEngine)->analyze($build, $intent, $ruleset);
        if ($analysisResult->isFailure() || ! $analysisResult->value() instanceof AnalysisResult) {
            throw new TerminalWorkflowFailure('deterministic_analysis_failed_closed', 'PoE2 deterministic analysis failed closed.');
        }
        $result = $analysisResult->value();
        $graphResult = $this->planner->plan($result, $intent, BudgetConstraint::unknown(), new UserConstraints);
        $graph = $graphResult->isSuccess() && $graphResult->value() instanceof UpgradeGraph ? $graphResult->value() : null;
        $input = CanonicalJson::encode(['build' => $build, 'ruleset' => $identity]);
        // UpgradeGraph nodes are candidate products; recommendation projection
        // is persisted by the application planner boundary, not this engine.
        $recommendations = [];
        $output = CanonicalJson::encode(['analysis_result' => $result, 'build_summary' => $build, 'findings' => $result->findings, 'recommendations' => $recommendations, 'manual_trade_recipes' => [], 'intent' => $intent, 'upgrade_graph' => $graph]);

        return new DeterministicAnalysisSnapshot('pob2-beta', $artifact->parserVersion ?? '', $context->rulesetId, $context->rulesetVersion, $context->rulesetChecksumSha256, $input, hash('sha256', $input), $output, hash('sha256', $output), $result->findings, $recommendations, []);
    }

    /** @return list<array<string, mixed>> */
    private function arrays(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $entry): bool => is_array($entry)));
    }

    /** @return list<string> */
    private function strings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $entry): bool => is_string($entry)));
    }

    /** @param array<string,mixed> $payload */
    private function hydrateBuild(array $payload): CanonicalImportedBuild
    {
        $propertySupport = [];
        foreach ((array) ($payload['property_support'] ?? []) as $property => $status) {
            if (is_string($property) && is_string($status) && PropertySupportStatus::tryFrom($status) instanceof PropertySupportStatus) {
                $propertySupport[$property] = PropertySupportStatus::from($status);
            }
        }
        $unsupported = [];
        foreach ($this->arrays($payload['unsupported_fields'] ?? null) as $entry) {
            if (is_string($entry['path'] ?? null) && is_string($entry['element'] ?? null) && is_array($entry['attributes'] ?? null)) {
                $unsupported[] = new UnsupportedFeature($entry['path'], $entry['element'], array_filter($entry['attributes'], 'is_string'));
            }
        }
        $warnings = [];
        foreach ($this->arrays($payload['warnings'] ?? null) as $entry) {
            if (is_string($entry['code'] ?? null) && is_string($entry['message'] ?? null)) {
                $warnings[] = new ImportWarning($entry['code'], $entry['message'], is_string($entry['path'] ?? null) ? $entry['path'] : null);
            }
        }
        $metadata = null;
        if (is_array($payload['source_metadata'] ?? null)) {
            $meta = $payload['source_metadata'];
            $inputType = is_string($meta['input_type'] ?? null) ? BuildInputType::tryFrom($meta['input_type']) : null;
            $detected = is_string($meta['detected_edition'] ?? null) ? GameEdition::tryFrom($meta['detected_edition']) : null;
            if (is_string($meta['source_id'] ?? null) && $inputType instanceof BuildInputType && $detected === GameEdition::Poe2 && is_string($meta['edition_evidence'] ?? null) && is_string($meta['input_checksum_sha256'] ?? null) && is_string($meta['parser_version'] ?? null)) {
                $metadata = new BuildSourceMetadata($meta['source_id'], $inputType, $detected, $meta['edition_evidence'], $meta['input_checksum_sha256'], $meta['parser_version']);
            }
        }

        return new CanonicalImportedBuild(
            GameEdition::Poe2,
            is_string($payload['build_version'] ?? null) ? $payload['build_version'] : null,
            is_int($payload['character_level'] ?? null) ? $payload['character_level'] : null,
            is_string($payload['character_class_id'] ?? null) ? $payload['character_class_id'] : null,
            is_string($payload['ascendancy_id'] ?? null) ? $payload['ascendancy_id'] : null,
            is_array($payload['choices'] ?? null) ? $payload['choices'] : [],
            $this->strings($payload['passive_node_ids'] ?? null),
            $this->arrays($payload['skills'] ?? null),
            $this->arrays($payload['items'] ?? null),
            is_array($payload['configuration'] ?? null) ? $payload['configuration'] : [],
            is_array($payload['summary_values'] ?? null) ? $payload['summary_values'] : [],
            is_string($payload['notes_untrusted_text'] ?? null) ? $payload['notes_untrusted_text'] : '',
            true,
            is_array($payload['attributes'] ?? null) ? $payload['attributes'] : [],
            $payload['life'] ?? null,
            $payload['energy_shield'] ?? null,
            $payload['mana'] ?? null,
            $payload['armour'] ?? null,
            $payload['evasion'] ?? null,
            is_array($payload['resistances'] ?? null) ? $payload['resistances'] : [],
            $this->arrays($payload['supports'] ?? null),
            $this->arrays($payload['auras'] ?? null),
            $this->arrays($payload['item_modifiers'] ?? null),
            $this->strings($payload['keystones'] ?? null),
            $this->arrays($payload['jewels'] ?? null),
            $this->arrays($payload['clusters'] ?? null),
            $propertySupport,
            $unsupported,
            $warnings,
            $metadata,
        );
    }

    private function validateCanonicalBuild(CanonicalImportedBuild $build, Poe2Ruleset $ruleset): void
    {
        $canonical = new Poe2CanonicalResolver($ruleset);
        if ($build->characterClassId !== null && str_starts_with($build->characterClassId, 'poe2.') && $canonical->characterClass($build->characterClassId) === null) {
            throw new TerminalWorkflowFailure('unknown_poe2_class', 'The PoE2 class is not present in the active canonical ruleset.');
        }
        if ($build->ascendancyId !== null && str_starts_with($build->ascendancyId, 'poe2.') && $canonical->ascendancy($build->ascendancyId) === null) {
            throw new TerminalWorkflowFailure('unknown_poe2_ascendancy', 'The PoE2 ascendancy is not present in the active canonical ruleset.');
        }
        $passives = new Poe2PassiveResolver($ruleset);
        foreach ($build->passiveNodeIds as $id) {
            if (str_starts_with($id, 'poe2.') && $passives->resolve($id) === null) {
                throw new TerminalWorkflowFailure('unknown_poe2_passive', 'A PoE2 passive is not present in the active canonical ruleset.');
            }
        }
        $skills = new Poe2SkillResolver($ruleset);
        foreach ($build->skills as $group) {
            foreach ($this->arrays($group['gems'] ?? null) as $gem) {
                $id = $gem['id'] ?? null;
                if (is_string($id) && str_starts_with($id, 'poe2.') && $skills->skill($id) === null && $skills->support($id) === null) {
                    throw new TerminalWorkflowFailure('unknown_poe2_skill', 'A PoE2 skill or support is not present in the active canonical ruleset.');
                }
            }
        }
        $modifiers = new Poe2ModifierResolver($ruleset);
        foreach ($build->itemModifiers as $modifier) {
            $id = $modifier['id'] ?? null;
            if (is_string($id) && str_starts_with($id, 'poe2.') && $modifiers->resolve($id) === null) {
                throw new TerminalWorkflowFailure('unknown_poe2_modifier', 'A PoE2 modifier is not present in the active canonical ruleset.');
            }
        }
    }
}
