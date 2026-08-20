<?php

namespace Tests\Unit\GameAdapters;

use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
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
        self::assertSame('2c8093a8f0628b411fc7fe4e31fa604ff228096243fa3ef44b90b546fa5a28d7', hash('sha256', CanonicalJson::encode($first)));
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
