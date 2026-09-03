<?php

namespace Tests\Unit\Domain;

use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\Analysis\AnalysisStatus;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Import\PropertySupportStatus;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\GameVersion;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisEngine;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisRuleset;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1ContentGoalRegistry;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1DeterministicAnalysisEngine;
use Lootwright\GameAdapters\PoE2\Analysis\Poe2AnalysisEngine;
use Lootwright\GameAdapters\PoE2\Analysis\Poe2ContentGoalRegistry;
use Lootwright\GameAdapters\PoE2\Analysis\Poe2RuleRegistry;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

final class DeterministicAnalysisEngineContractTest extends TestCase
{
    public function test_poe1_golden_result_is_byte_stable_and_contains_complete_trace_contract(): void
    {
        $result = $this->poe1()->analyze($this->poe1Build(), DomainFixtures::intent(GameEdition::Poe1), $this->ruleset(GameEdition::Poe1));
        self::assertTrue($result->isSuccess());
        self::assertInstanceOf(AnalysisResult::class, $result->value());
        $json = $result->value()->canonicalJson();
        self::assertSame($json, $this->poe1()->analyze($this->poe1Build(), DomainFixtures::intent(GameEdition::Poe1), $this->ruleset(GameEdition::Poe1))->value()->canonicalJson());
        $payload = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        $golden = json_decode((string) file_get_contents(__DIR__.'/../../Fixtures/Analysis/poe1-golden.json'), true, 64, JSON_THROW_ON_ERROR);
        self::assertSame($golden, [
            'game_edition' => $payload['game_edition'],
            'status' => $payload['status'],
            'engine_version' => $payload['engine_version'],
            'rule_codes' => array_column($payload['findings'], 'rule_id'),
            'recommendation_count' => count($payload['recommendations']),
            'unsupported_data' => $payload['unsupported_data'],
        ]);
        $finding = json_decode($json, true, 64, JSON_THROW_ON_ERROR)['findings'][0];
        foreach (['finding_id', 'game_edition', 'ruleset_version', 'category', 'severity', 'affected_entity', 'evidence', 'rule_id', 'source_provenance', 'confidence_basis_points', 'unsupported_data', 'dependencies', 'explanation_trace'] as $field) {
            self::assertArrayHasKey($field, $finding);
        }
    }

    public function test_poe2_golden_result_is_deterministic_and_does_not_borrow_poe1_rules(): void
    {
        $result = (new Poe2AnalysisEngine)->analyze($this->poe2Build(), DomainFixtures::intent(GameEdition::Poe2), $this->ruleset(GameEdition::Poe2));
        self::assertTrue($result->isSuccess());
        self::assertSame(AnalysisStatus::Complete, $result->value()->status);
        self::assertSame([
            'poe2.data.character.level.missing',
            'poe2.data.character.class.missing',
            'poe2.data.character.ascendancy.missing',
            'poe2.skills.main.missing',
            'poe2.data.resistances.unavailable',
        ], array_map(static fn ($finding): string => $finding->code, $result->value()->findings));
        $payload = json_decode($result->value()->canonicalJson(), true, 64, JSON_THROW_ON_ERROR);
        $golden = json_decode((string) file_get_contents(__DIR__.'/../../Fixtures/Analysis/poe2-golden.json'), true, 64, JSON_THROW_ON_ERROR);
        self::assertSame($golden, [
            'game_edition' => $payload['game_edition'],
            'status' => $payload['status'],
            'engine_version' => $payload['engine_version'],
            'finding_count' => count($payload['findings']),
            'unsupported_data' => $payload['unsupported_data'],
        ]);
    }

    public function test_cross_edition_inputs_are_rejected_in_both_directions(): void
    {
        $poe1 = $this->poe1()->analyze($this->poe2Build(), DomainFixtures::intent(GameEdition::Poe2), $this->ruleset(GameEdition::Poe2));
        $poe2 = (new Poe2AnalysisEngine)->analyze($this->poe1Build(), DomainFixtures::intent(GameEdition::Poe1), $this->ruleset(GameEdition::Poe1));

        self::assertSame(DomainErrorCode::EditionMismatch, $poe1->error()->code);
        self::assertSame(DomainErrorCode::EditionMismatch, $poe2->error()->code);
    }

    public function test_poe2_rejects_foreign_poe1_identifiers_even_when_the_container_is_approved(): void
    {
        $identity = DomainFixtures::ruleset(GameEdition::Poe2);
        $approved = new GameRuleset(
            $identity,
            new GameVersion(GameEdition::Poe2, $identity->patch),
            DatasetClassification::ApprovedImport,
            ProvenanceStatus::Approved,
            RulesetCompatibilityStatus::Compatible,
        );
        $foreignBuilds = [
            'passive' => new CanonicalImportedBuild(GameEdition::Poe2, '2.3.4', 70, 'poe2.pob.class.warrior', 'poe2.pob.ascendancy.warbringer', [], ['poe1.pob.node.101'], [], [], [], [], '', true),
            'ascendancy' => new CanonicalImportedBuild(GameEdition::Poe2, '2.3.4', 70, 'poe2.pob.class.warrior', 'poe1.pob.ascendancy.champion', [], [], [], [], [], [], '', true),
            'skill' => new CanonicalImportedBuild(GameEdition::Poe2, '2.3.4', 70, 'poe2.pob.class.warrior', 'poe2.pob.ascendancy.warbringer', [], [], [['id' => 'poe2.pob.skill_group.1', 'gems' => [['id' => 'poe1.pob.gem.fireball']]]], [], [], [], '', true),
            'modifier' => new CanonicalImportedBuild(GameEdition::Poe2, '2.3.4', 70, 'poe2.pob.class.warrior', 'poe2.pob.ascendancy.warbringer', [], [], [], [], [], [], '', true, itemModifiers: [['id' => 'poe1.mod.maximum_life']]),
        ];

        foreach ($foreignBuilds as $label => $foreign) {
            $result = (new Poe2AnalysisEngine)->analyze($foreign, DomainFixtures::intent(GameEdition::Poe2), $approved);
            self::assertTrue($result->isFailure(), $label);
            self::assertSame(DomainErrorCode::InvalidValue, $result->error()->code, $label);
        }
    }

    public function test_explicitly_unsupported_input_suppresses_dependent_rule_and_is_disclosed(): void
    {
        $build = new CanonicalImportedBuild(
            GameEdition::Poe1,
            '1.2.3',
            90,
            'poe1:witch',
            'poe1:occultist',
            [],
            [],
            [],
            [],
            [],
            [],
            '',
            false,
            propertySupport: ['items' => PropertySupportStatus::Unsupported],
        );
        $result = $this->poe1()->analyze($build, DomainFixtures::intent(GameEdition::Poe1), $this->ruleset(GameEdition::Poe1))->value();

        self::assertNotContains('equipment.required_slot.empty', array_column($result->findings, 'code'));
        self::assertContains('property:items:unsupported', $result->unsupportedData);
    }

    public function test_rule_and_content_goal_registries_are_edition_scoped(): void
    {
        $poe1Goals = new Poe1ContentGoalRegistry;
        self::assertSame(['mapping', 'bossing', 'delve', 'simulacrum', 'sanctum', 'progression'], $poe1Goals->identifiers());
        self::assertSame(['progression', 'mapping', 'bossing'], (new Poe2ContentGoalRegistry)->identifiers());
        self::assertCount(5, (new Poe2RuleRegistry(DomainFixtures::ruleset(GameEdition::Poe2)->version))->rules());

        $this->expectException(\InvalidArgumentException::class);
        new Poe2RuleRegistry(DomainFixtures::ruleset(GameEdition::Poe1)->version);
    }

    #[Group('benchmark')]
    public function test_bounded_fixture_executes_without_obvious_per_rule_io_cost(): void
    {
        $engine = $this->poe1();
        $build = $this->poe1Build();
        $intent = DomainFixtures::intent(GameEdition::Poe1);
        $ruleset = $this->ruleset(GameEdition::Poe1);
        $started = hrtime(true);
        for ($i = 0; $i < 200; $i++) {
            self::assertTrue($engine->analyze($build, $intent, $ruleset)->isSuccess());
        }
        $milliseconds = (hrtime(true) - $started) / 1_000_000;

        self::assertLessThan(1_000, $milliseconds, 'A 200-run in-memory benchmark exceeded the generous regression budget.');
    }

    private function poe1(): Poe1AnalysisEngine
    {
        return new Poe1AnalysisEngine(new Poe1DeterministicAnalysisEngine, Poe1AnalysisRuleset::publishedV1(), ['101' => true]);
    }

    private function ruleset(GameEdition $edition): GameRuleset
    {
        $identity = DomainFixtures::ruleset($edition);

        return new GameRuleset($identity, new GameVersion($edition, $identity->patch), DatasetClassification::ApprovedImport, ProvenanceStatus::Approved, RulesetCompatibilityStatus::Compatible);
    }

    private function poe1Build(): CanonicalImportedBuild
    {
        return new CanonicalImportedBuild(
            GameEdition::Poe1,
            '1.2.3',
            90,
            'poe1:ranger',
            'poe1:deadeye',
            [],
            ['poe1.pob.node.101'],
            [],
            [
                ['id' => 'helmet', 'slots' => ['Helmet']],
                ['id' => 'body', 'slots' => ['Body Armour']],
                ['id' => 'gloves', 'slots' => ['Gloves']],
                ['id' => 'boots', 'slots' => ['Boots']],
            ],
            [],
            ['FireResist' => 70, 'FireResistCap' => 75, 'ChaosResist' => -5],
            '',
            false,
        );
    }

    private function poe2Build(): CanonicalImportedBuild
    {
        return new CanonicalImportedBuild(GameEdition::Poe2, '2.3.4', null, null, null, [], [], [], [], [], [], '', true);
    }
}
