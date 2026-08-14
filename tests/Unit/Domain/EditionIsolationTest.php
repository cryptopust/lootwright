<?php

namespace Tests\Unit\Domain;

use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\PoeCatalog\Identifier\AffixId;
use Lootwright\Domain\PoeCatalog\Identifier\ItemBaseId;
use Lootwright\Domain\PoeCatalog\Identifier\ItemId;
use Lootwright\Domain\PoeCatalog\Identifier\ItemSlotId;
use Lootwright\Domain\PoeCatalog\Item\CanonicalItem;
use Lootwright\Domain\PoeCatalog\Item\ItemRarity;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\ParserVersion;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

class EditionIsolationTest extends TestCase
{
    public function test_poe1_identifier_cannot_enter_a_poe2_item(): void
    {
        $result = CanonicalItem::create(
            GameEdition::Poe2,
            DomainFixtures::value(ItemId::from(GameEdition::Poe2, 'fixture.item'), ItemId::class),
            DomainFixtures::value(ItemBaseId::from(GameEdition::Poe2, 'fixture.base'), ItemBaseId::class),
            DomainFixtures::value(ItemSlotId::from(GameEdition::Poe2, 'fixture.slot'), ItemSlotId::class),
            ItemRarity::Rare,
            affixes: [DomainFixtures::value(
                AffixId::from(GameEdition::Poe1, 'fixture.affix'),
                AffixId::class,
            )],
        );

        self::assertSame(DomainErrorCode::EditionMismatch, $result->error()->code);
    }

    public function test_poe1_snapshot_cannot_use_a_poe2_ruleset(): void
    {
        $result = CanonicalBuild::create(
            DomainFixtures::snapshot(GameEdition::Poe1),
            DomainFixtures::ruleset(GameEdition::Poe2),
        );

        self::assertSame(DomainErrorCode::EditionMismatch, $result->error()->code);
    }

    public function test_patch_mismatch_fails_before_analysis(): void
    {
        $snapshot = DomainFixtures::snapshot(GameEdition::Poe1);
        $differentPatch = DomainFixtures::patch(GameEdition::Poe1, '1.2.4');
        $ruleset = DomainFixtures::ruleset(GameEdition::Poe1, $differentPatch);

        $result = CanonicalBuild::create($snapshot, $ruleset);

        self::assertSame(DomainErrorCode::PatchMismatch, $result->error()->code);
    }

    public function test_parser_mismatch_fails_before_analysis(): void
    {
        $snapshot = DomainFixtures::snapshot(GameEdition::Poe1);
        $ruleset = DomainFixtures::ruleset(GameEdition::Poe1);
        $differentParser = DomainFixtures::value(
            ParserVersion::from(GameEdition::Poe1, '2.0.0'),
            ParserVersion::class,
        );
        $mismatched = RulesetIdentity::create(
            $ruleset->edition,
            $ruleset->id,
            $ruleset->version,
            $ruleset->patch,
            $ruleset->league,
            $differentParser,
            $ruleset->checksumSha256,
            $ruleset->provenance,
        );

        $result = CanonicalBuild::create(
            $snapshot,
            DomainFixtures::value($mismatched, RulesetIdentity::class),
        );

        self::assertSame(DomainErrorCode::ParserMismatch, $result->error()->code);
    }
}
