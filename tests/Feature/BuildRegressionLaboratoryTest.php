<?php

namespace Tests\Feature;

use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\Analysis\AnalysisStatus;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Analysis\RecommendationCandidate;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\GameVersion;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisRuleset;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1DeterministicAnalysisEngine;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Normalizer;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Parser;
use Lootwright\GameAdapters\PoE2\Analysis\Poe2AnalysisEngine;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Normalizer;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Parser;
use Lootwright\GameAdapters\Shared\Pob\PobEnvelopeDecoder;
use Lootwright\GameAdapters\Shared\Pob\PobImportCoordinator;
use Lootwright\GameAdapters\Shared\Pob\SafeXmlParser;
use RuntimeException;
use Tests\Support\DomainFixtures;
use Tests\TestCase;

final class BuildRegressionLaboratoryTest extends TestCase
{
    public function test_poe1_real_shaped_corpus_matches_approved_golden_projections(): void
    {
        $manifest = $this->manifest('Poe1/manifest.json');
        $golden = $this->manifest('Poe1/golden.json');
        $engine = new Poe1DeterministicAnalysisEngine;
        $ruleset = DomainFixtures::ruleset(GameEdition::Poe1);
        $seenArchetypes = [];

        foreach ($manifest['cases'] as $case) {
            $id = $case['id'];
            $imported = $this->success($this->poe1()->import($this->xml('Poe1/'.$case['file'])));
            $build = $imported->canonicalBuild;
            $seenArchetypes = [...$seenArchetypes, ...$case['archetypes']];

            self::assertSame(GameEdition::Poe1, $build->edition, $id);
            self::assertSame($case['class'], $build->characterClassId, $id);
            self::assertSame($case['ascendancy'], $build->ascendancyId, $id);
            self::assertSame($case['main_skill'], $build->skills[0]['gems'][0]['id'] ?? null, $id);
            self::assertNotSame('', (string) $case['defence_profile'], $id.' defence profile metadata is required');
            $unsupportedElements = array_map(static fn ($feature): string => $feature->element, $build->unsupportedFields);
            self::assertContains('ItemSet', $unsupportedElements, $id.' should expose the unconsumed ItemSet shape');

            $findings = $engine->analyze($build, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['corpus_case' => $id]);
            $replay = $engine->analyze($build, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['corpus_case' => $id]);
            self::assertSame(CanonicalJson::encode($findings), CanonicalJson::encode($replay), $id.' deterministic replay changed bytes');
            $codes = array_map(static fn ($finding): string => $finding->code, $findings);
            self::assertSame($golden['cases'][$id]['finding_codes'], $codes, $id.' golden finding set changed');
            self::assertSame([], array_values(array_intersect($case['forbidden_findings'], $codes)), $id.' emitted a forbidden finding');

            $snapshot = $this->projection(new AnalysisResult(GameEdition::Poe1, $ruleset, Poe1DeterministicAnalysisEngine::ENGINE_VERSION, AnalysisStatus::Complete, $findings));
            self::assertSame($golden['engine_version'], $snapshot['engine_version'], $id);
            self::assertSame($golden['ruleset_version'], $snapshot['ruleset_version'], $id);
            self::assertSame($golden['cases'][$id]['unsupported_data'], $snapshot['unsupported_data'], $id);
            self::assertSame($golden['cases'][$id]['finding_severities'] ?? [], $snapshot['finding_severities'], $id.' finding severity changed');
            self::assertSame($golden['cases'][$id]['recommendation_priorities'] ?? [], $snapshot['recommendation_priorities'], $id.' recommendation priority changed');
        }

        self::assertGreaterThanOrEqual(8, count($manifest['cases']));
        self::assertContains('melee', $seenArchetypes);
        self::assertContains('ranged_attack', $seenArchetypes);
        self::assertContains('spell', $seenArchetypes);
        self::assertContains('minion', $seenArchetypes);
        self::assertContains('totem', $seenArchetypes);
        self::assertContains('mine', $seenArchetypes);
        self::assertContains('trap', $seenArchetypes);
        self::assertContains('DoT', $seenArchetypes);
    }

    public function test_poe2_corpus_is_edition_isolated_and_fail_closed(): void
    {
        $manifest = $this->manifest('Poe2/manifest.json');
        $golden = $this->manifest('Poe2/golden.json');
        $engine = new Poe2AnalysisEngine;
        $identity = DomainFixtures::ruleset(GameEdition::Poe2);
        $ruleset = new GameRuleset($identity, new GameVersion(GameEdition::Poe2, $identity->patch), DatasetClassification::Unavailable, ProvenanceStatus::Pending, RulesetCompatibilityStatus::Unavailable);

        foreach ($manifest['cases'] as $case) {
            $id = $case['id'];
            $imported = $this->success($this->poe2()->import($this->xml('Poe2/'.$case['file'])));
            self::assertSame(GameEdition::Poe2, $imported->canonicalBuild->edition, $id);
            self::assertStringStartsWith('poe2.', (string) $imported->canonicalBuild->characterClassId, $id);
            self::assertStringStartsWith('poe2.', (string) ($imported->canonicalBuild->skills[0]['gems'][0]['id'] ?? ''), $id);
            $result = $engine->analyze($imported->canonicalBuild, DomainFixtures::intent(GameEdition::Poe2), $ruleset);
            self::assertTrue($result->isSuccess(), $id);
            $analysis = $result->value();
            self::assertInstanceOf(AnalysisResult::class, $analysis, $id);
            self::assertSame($golden['cases'][$id]['status'], $analysis->status->value, $id);
            self::assertSame($golden['cases'][$id]['unsupported_data'], $analysis->unsupportedData, $id);
            self::assertSame([], $analysis->findings, $id.' leaked PoE1 findings into PoE2');
        }
    }

    public function test_mutations_detect_supported_defects_and_healthy_builds_stay_clean(): void
    {
        $imported = $this->success($this->poe1()->import($this->xml('Poe1/melee-armour-mapping.xml')));
        $build = $imported->canonicalBuild;
        $engine = new Poe1DeterministicAnalysisEngine;
        $ruleset = DomainFixtures::ruleset(GameEdition::Poe1);

        self::assertSame([], $engine->analyze($build, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['mutation' => 'healthy']));

        $resistance = $this->copyBuild($build, summary: [...$build->summaryValues, 'FireResist' => 60]);
        self::assertContains('defence.fire_resistance.below_reported_max', $this->codes($engine->analyze($resistance, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['mutation' => 'remove_resistance'])));

        $reservation = $this->copyBuild($build, summary: [...$build->summaryValues, 'ReservedMana' => 1_001]);
        self::assertContains('resources.mana.reservation_invalid', $this->codes($engine->analyze($reservation, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['mutation' => 'break_reservation'])));

        $passiveConflict = $this->copyBuild($build, passives: ['poe1.pob.node.999999']);
        self::assertContains('passive_tree.node.unknown', $this->codes($engine->analyze($passiveConflict, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['mutation' => 'passive_conflict'])));

        $missingAttribute = $this->copyBuild($build, summary: [...$build->summaryValues, 'UnreservedMana' => -1]);
        self::assertContains('resources.mana.unreserved_negative', $this->codes($engine->analyze($missingAttribute, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['mutation' => 'resource_starvation'])));

        $disabledGem = $this->copyBuild($build, skills: [[...$build->skills[0], 'gems' => [[...$build->skills[0]['gems'][0], 'enabled' => false], ...array_slice($build->skills[0]['gems'], 1)]]]);
        self::assertContains('skills.gem.disabled', $this->codes($engine->analyze($disabledGem, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['mutation' => 'disabled_support'])));

        $missingDefenceLayer = $this->copyBuild($build, skills: $build->skills, summary: [...$build->summaryValues, 'ColdResist' => 60]);
        self::assertContains('defence.cold_resistance.below_reported_max', $this->codes($engine->analyze($missingDefenceLayer, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['mutation' => 'remove_defence_layer'])));

        $missingUnique = $this->copyBuild($build, skills: $build->skills, passives: $build->passiveNodeIds);
        self::assertNotContains('equipment.required_unique.missing', $this->codes($engine->analyze($missingUnique, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['mutation' => 'remove_required_unique'])));

        // Mechanics without an approved rule are not converted into guesses.
        $compatibility = $this->copyBuild($build, skills: [[...$build->skills[0], 'gems' => [[$build->skills[0]['gems'][0], 'enabled' => true]]]]);
        self::assertNotContains('skills.support_incompatible', $this->codes($engine->analyze($compatibility, DomainFixtures::analysisId(GameEdition::Poe1), $ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['mutation' => 'break_support_compatibility'])));
    }

    /** @return list<string> */
    /** @param list<Finding> $findings
     * @return list<string>
     */
    private function codes(array $findings): array
    {
        return array_map(static fn (Finding $finding): string => $finding->code, $findings);
    }

    /** @return array<string, mixed> */
    private function projection(AnalysisResult $result): array
    {
        return ['game_edition' => $result->gameEdition->value, 'engine_version' => $result->engineVersion, 'ruleset_version' => $result->ruleset->version->value, 'status' => $result->status->value, 'finding_codes' => $this->codes($result->findings), 'finding_severities' => array_map(static fn (Finding $finding): string => (string) $finding->severity->value, $result->findings), 'recommendation_priorities' => array_map(static fn (RecommendationCandidate $recommendation): string => $recommendation->id, $result->recommendations), 'unsupported_data' => $result->unsupportedData];
    }

    /** @param list<string>|null $passives
     * @param  list<array<string,mixed>>|null  $skills
     * @param  array<string,int|string>|null  $summary
     */
    private function copyBuild(CanonicalImportedBuild $build, ?array $summary = null, ?array $passives = null, ?array $skills = null): CanonicalImportedBuild
    {
        return new CanonicalImportedBuild($build->edition, $build->buildVersion, $build->characterLevel, $build->characterClassId, $build->ascendancyId, $build->choices, $passives ?? $build->passiveNodeIds, $skills ?? $build->skills, $build->items, $build->configuration, $summary ?? $build->summaryValues, $build->notes, $build->beta, $build->attributes, $build->life, $build->energyShield, $build->mana, $build->armour, $build->evasion, $build->resistances, $build->supports, $build->auras, $build->itemModifiers, $build->keystones, $build->jewels, $build->clusters, $build->propertySupport, $build->unsupportedFields, $build->warnings, $build->sourceMetadata);
    }

    private function poe1(): PobImportCoordinator
    {
        return new PobImportCoordinator(new PobEnvelopeDecoder, new SafeXmlParser, [new Pob1Parser(new Pob1Normalizer)]);
    }

    private function poe2(): PobImportCoordinator
    {
        return new PobImportCoordinator(new PobEnvelopeDecoder, new SafeXmlParser, [new Pob2Parser(new Pob2Normalizer)]);
    }

    /** @return array<string, mixed> */
    private function manifest(string $relative): array
    {
        $decoded = json_decode((string) file_get_contents(base_path('tests/Fixtures/Builds/'.$relative)), true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : throw new RuntimeException('Regression manifest is invalid.');
    }

    private function xml(string $relative): string
    {
        $contents = file_get_contents(base_path('tests/Fixtures/Builds/'.$relative));

        return is_string($contents) ? $contents : throw new RuntimeException('Regression fixture is unreadable.');
    }

    private function success(DomainResult $result): PobImportResult
    {
        if ($result->isFailure() || ! $result->value() instanceof PobImportResult) {
            self::fail($result->isFailure() ? $result->error()->code->value.': '.$result->error()->message : 'Unexpected regression import result.');
        }

        return $result->value();
    }
}
