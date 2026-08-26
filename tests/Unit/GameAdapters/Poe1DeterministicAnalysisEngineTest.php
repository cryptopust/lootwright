<?php

namespace Tests\Unit\GameAdapters;

use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\BuildIntake\Intent\ContentGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayerGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayStyle;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\GameVersion;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\Shared\Value\Confidence;
use Lootwright\Domain\Shared\Value\Locale;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisEngine;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisRuleset;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1DeterministicAnalysisEngine;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1PlayerStatAliasRegistry;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

final class Poe1DeterministicAnalysisEngineTest extends TestCase
{
    public function test_alias_registry_accepts_only_unambiguous_tested_pob_names(): void
    {
        $registry = new Poe1PlayerStatAliasRegistry;
        self::assertSame([
            'fire_resistance' => 70,
            'maximum_fire_resistance' => 75,
        ], $registry->canonicalize(['FireResist' => 70, 'FireResistCap' => 75]));
        self::assertArrayNotHasKey('fire_resistance', $registry->canonicalize(['FireResist' => 70, 'FireResistance' => 71]));
    }

    public function test_controlled_rules_emit_complete_deterministic_findings(): void
    {
        $build = $this->build(
            level: null,
            class: null,
            ascendancy: null,
            passives: ['poe1.pob.node.101', 'poe1.pob.node.999'],
            skills: [[
                'id' => 'poe1.pob.skill_group.1',
                'slot' => 'Body Armour',
                'enabled' => true,
                'main_active_gem_index' => 1,
                'gems' => [
                    ['id' => 'poe1.pob.gem.main', 'enabled' => true],
                    ['id' => 'poe1.pob.gem.support-one', 'enabled' => true],
                    ['id' => 'poe1.pob.gem.support-two', 'enabled' => false],
                ],
            ]],
            items: [
                ['id' => 'item-a', 'slots' => ['Helmet', 'Helmet']],
                ['id' => 'item-b', 'slots' => ['Helmet', 'Gloves']],
            ],
            stats: [
                'FireResist' => 70, 'FireResistCap' => 75,
                'ColdResistance' => 74, 'MaximumColdResistance' => 75,
                'LightningResist' => 80, 'LightningResistCap' => 80,
                'ChaosResist' => -12,
                'UnreservedMana' => -1,
                'ReservedMana' => 101,
                'TotalMana' => 100,
            ],
        );
        $findings = $this->engine()->analyze($build, DomainFixtures::analysisId(GameEdition::Poe1), DomainFixtures::canonicalBuild(GameEdition::Poe1)->ruleset, Poe1AnalysisRuleset::publishedV1(), [(string) 101 => true], ['snapshot' => 'fixture']);
        $codes = array_column($findings, 'code');

        self::assertSame([
            'data.character.level.missing',
            'data.character.class.missing',
            'data.character.ascendancy.missing',
            'equipment.required_slot.empty',
            'defence.fire_resistance.below_reported_max',
            'defence.cold_resistance.below_reported_max',
            'defence.chaos_resistance.negative',
            'resources.mana.unreserved_negative',
            'resources.mana.reservation_invalid',
            'skills.gem.disabled',
            'skills.main.insufficient_links',
            'equipment.slot.conflict',
            'passive_tree.node.unknown',
        ], $codes);
        foreach ($findings as $finding) {
            $payload = $finding->jsonSerialize();
            foreach (['code', 'severity', 'category', 'title', 'deterministic_explanation', 'observed_value', 'expected_value', 'affected_slots', 'affected_gems', 'affected_nodes', 'input_evidence', 'source_provenance', 'confidence_basis_points', 'rule'] as $key) {
                self::assertArrayHasKey($key, $payload);
            }
            self::assertSame('1.0.0', $payload['rule']->rulesetVersion->value);
        }
    }

    public function test_missing_stats_do_not_throw_or_invent_threshold_findings(): void
    {
        $findings = $this->engine()->analyze(
            $this->build(level: 90, class: 'poe1.pob.class.ranger', ascendancy: 'poe1.pob.ascendancy.deadeye', passives: [], skills: [], items: $this->coreItems(), stats: []),
            DomainFixtures::analysisId(GameEdition::Poe1),
            DomainFixtures::canonicalBuild(GameEdition::Poe1)->ruleset,
            Poe1AnalysisRuleset::publishedV1(),
            [],
            ['snapshot' => 'fixture'],
        );

        self::assertSame([], $findings);
        self::assertNotContains('defence.life.low', Poe1DeterministicAnalysisEngine::RULE_CODES);
        self::assertNotContains('offence.dps.low', Poe1DeterministicAnalysisEngine::RULE_CODES);
    }

    public function test_same_semantic_input_has_byte_stable_output_regardless_of_map_order(): void
    {
        $one = ['FireResist' => 70, 'FireResistCap' => 75, 'ChaosResist' => -1];
        $two = array_reverse($one, true);
        $engine = $this->engine();
        $ruleset = DomainFixtures::canonicalBuild(GameEdition::Poe1)->ruleset;
        $id = DomainFixtures::analysisId(GameEdition::Poe1);
        $manifest = Poe1AnalysisRuleset::publishedV1();
        $first = $engine->analyze($this->build(90, 'poe1.pob.class.ranger', 'poe1.pob.ascendancy.deadeye', [], [], $this->coreItems(), $one), $id, $ruleset, $manifest, [], ['snapshot' => 'fixture']);
        $second = $engine->analyze($this->build(90, 'poe1.pob.class.ranger', 'poe1.pob.ascendancy.deadeye', [], [], array_reverse($this->coreItems()), $two), $id, $ruleset, $manifest, [], ['snapshot' => 'fixture']);

        self::assertSame(CanonicalJson::encode($first), CanonicalJson::encode($second));
        self::assertSame('a17abaacfc86e7abd50e7da6f551650fce9d03dd31e087823a608171a7336703', hash('sha256', CanonicalJson::encode($first)));
    }

    public function test_explicit_attribute_and_mana_requirements_are_reported_without_inference(): void
    {
        $build = $this->build(90, 'poe1.pob.class.ranger', 'poe1.pob.ascendancy.deadeye', [], [], $this->coreItems(), [
            'Strength' => 80,
            'StrengthRequirement' => 100,
            'ManaCost' => 120,
            'UnreservedMana' => 80,
        ]);
        $codes = array_column($this->engine()->analyze($build, DomainFixtures::analysisId(GameEdition::Poe1), DomainFixtures::canonicalBuild(GameEdition::Poe1)->ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['test' => 'requirements']), 'code');

        self::assertContains('attributes.requirement.missing', $codes);
        self::assertContains('resources.mana.cost_unsustainable', $codes);
    }

    public function test_content_goal_from_analysis_intent_selects_the_profile(): void
    {
        $build = new CanonicalImportedBuild(GameEdition::Poe1, '3.29.1', 90, 'poe1.pob.class.ranger', 'poe1.pob.ascendancy.deadeye', [], [], [], $this->coreItems(), [], ['Life' => 4000], '', false);
        $ruleset = DomainFixtures::canonicalBuild(GameEdition::Poe1)->ruleset;
        $intent = $this->intent('bossing');
        $result = (new Poe1AnalysisEngine($this->engine(), Poe1AnalysisRuleset::publishedV1()))->analyze($build, $intent, new GameRuleset($ruleset, new GameVersion(GameEdition::Poe1, $ruleset->patch), DatasetClassification::ApprovedImport, ProvenanceStatus::Approved, RulesetCompatibilityStatus::Compatible))->value();

        self::assertContains('defence.life.below_content_profile', array_column($result->findings, 'code'));
        $life = array_values(array_filter($result->findings, static fn ($finding): bool => $finding->code === 'defence.life.below_content_profile'))[0];
        self::assertSame(4500, $life->expectedValue);
    }

    public function test_ci_build_does_not_receive_a_life_threshold_finding(): void
    {
        $build = new CanonicalImportedBuild(GameEdition::Poe1, '3.29.1', 90, 'poe1.pob.class.witch', 'poe1.pob.ascendancy.occultist', [], [], [], $this->coreItems(), [], ['Life' => 1, 'EnergyShield' => 6000], '', false, keystones: ['poe1.pob.keystone.chaos_inoculation']);
        $findings = $this->engine()->analyze($build, DomainFixtures::analysisId(GameEdition::Poe1), DomainFixtures::canonicalBuild(GameEdition::Poe1)->ruleset, Poe1AnalysisRuleset::publishedV1(), [], ['test' => 'ci']);

        self::assertNotContains('defence.life.below_content_profile', array_column($findings, 'code'));
    }

    private function intent(string $content): BuildIntent
    {
        $goal = PlayerGoal::create(GameEdition::Poe1, 'Test content goal', ContentGoal::from(GameEdition::Poe1, $content)->value(), PlayStyle::from(GameEdition::Poe1, 'balanced')->value())->value();

        return BuildIntent::create($goal, Locale::from('en-US')->value(), Confidence::fromBasisPoints(10_000)->value(), [])->value();
    }

    private function engine(): Poe1DeterministicAnalysisEngine
    {
        return new Poe1DeterministicAnalysisEngine;
    }

    /** @return list<array<string, mixed>> */
    private function coreItems(): array
    {
        return [
            ['id' => 'helmet', 'slots' => ['Helmet']],
            ['id' => 'body', 'slots' => ['Body Armour']],
            ['id' => 'gloves', 'slots' => ['Gloves']],
            ['id' => 'boots', 'slots' => ['Boots']],
        ];
    }

    /**
     * @param  list<string>  $passives
     * @param  list<array<string, mixed>>  $skills
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, int|string>  $stats
     */
    private function build(?int $level, ?string $class, ?string $ascendancy, array $passives, array $skills, array $items, array $stats): CanonicalImportedBuild
    {
        return new CanonicalImportedBuild(GameEdition::Poe1, '3.29.1', $level, $class, $ascendancy, [], $passives, $skills, $items, [], $stats, '', false);
    }
}
