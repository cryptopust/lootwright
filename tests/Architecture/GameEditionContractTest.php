<?php

namespace Tests\Architecture;

use Lootwright\Application\AIGateway\Schema\StrictJsonSchemaValidator;
use Lootwright\Application\AIGateway\Schema\StructuredSchemas;
use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\PoeCatalog\BuildCatalog;
use Lootwright\Domain\PoeCatalog\Identifier\CharacterClassId;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

final class GameEditionContractTest extends TestCase
{
    /** @return iterable<string, array{GameEdition, GameEdition}> */
    public static function crossEditionPairs(): iterable
    {
        yield 'PoE1 data into PoE2 analysis' => [GameEdition::Poe1, GameEdition::Poe2];
        yield 'PoE2 data into PoE1 analysis' => [GameEdition::Poe2, GameEdition::Poe1];
    }

    #[DataProvider('crossEditionPairs')]
    public function test_an_analysis_cannot_load_a_build_snapshot_from_another_edition(
        GameEdition $snapshotEdition,
        GameEdition $rulesetEdition,
    ): void {
        $result = CanonicalBuild::create(
            DomainFixtures::snapshot($snapshotEdition),
            DomainFixtures::ruleset($rulesetEdition),
        );

        self::assertTrue($result->isFailure());
        self::assertSame(DomainErrorCode::EditionMismatch, $result->error()->code);
    }

    public function test_every_ruleset_identity_is_edition_scoped(): void
    {
        $poe1 = DomainFixtures::ruleset(GameEdition::Poe1);

        $result = RulesetIdentity::create(
            GameEdition::Poe2,
            $poe1->id,
            $poe1->version,
            $poe1->patch,
            $poe1->league,
            $poe1->parserVersion,
            $poe1->checksumSha256,
            $poe1->provenance,
        );

        self::assertTrue($result->isFailure());
        self::assertSame(DomainErrorCode::EditionMismatch, $result->error()->code);
    }

    public function test_every_build_snapshot_is_edition_scoped(): void
    {
        $poe1 = DomainFixtures::snapshot(GameEdition::Poe1);
        $poe2Class = DomainFixtures::value(
            CharacterClassId::from(GameEdition::Poe2, 'witch'),
            CharacterClassId::class,
        );
        $poe2Catalog = DomainFixtures::value(
            BuildCatalog::create(GameEdition::Poe2, $poe2Class, null),
            BuildCatalog::class,
        );

        $result = BuildSnapshot::create(
            $poe1->buildId,
            $poe1->scope,
            $poe1->patch,
            $poe1->league,
            $poe1->parserVersion,
            $poe1->locale,
            $poe2Catalog,
            $poe1->inputDigestSha256,
        );

        self::assertTrue($result->isFailure());
        self::assertSame(DomainErrorCode::EditionMismatch, $result->error()->code);
    }

    public function test_every_recommendation_identifies_its_edition_and_ruleset(): void
    {
        $build = DomainFixtures::canonicalBuild(GameEdition::Poe1);
        $recommendation = DomainFixtures::recommendation($build);
        $payload = json_decode(CanonicalJson::encode($recommendation), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('poe1', $payload['edition']);
        self::assertSame('poe1', $payload['trace']['edition']);
        self::assertSame($build->ruleset->id->value, $payload['trace']['steps'][0]['rule']['ruleset_id']['value']);
        self::assertSame($build->ruleset->version->value, $payload['trace']['steps'][0]['rule']['ruleset_version']['value']);
    }

    public function test_ai_explanation_schema_cannot_inject_canonical_game_facts(): void
    {
        $schema = StructuredSchemas::explanation('en', ['poe1.resistance.fire'], ['poe1.upgrade.helmet']);
        $candidate = [
            'language' => 'en',
            'summary' => 'Explanation only.',
            'findings' => [['code' => 'poe1.resistance.fire', 'text' => 'Existing finding.']],
            'recommendations' => [['code' => 'poe1.upgrade.helmet', 'text' => 'Existing recommendation.']],
            'canonical_facts' => [['modifier_id' => 'invented.modifier', 'value' => 99]],
        ];

        $errors = (new StrictJsonSchemaValidator)->validate($candidate, $schema);

        self::assertNotSame([], $errors);
        self::assertContains('$.canonical_facts is not allowed.', $errors);
    }
}
