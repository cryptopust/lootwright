<?php

namespace Tests\Unit\Domain;

use Lootwright\Domain\PoeCatalog\BuildCatalog;
use Lootwright\Domain\PoeCatalog\Character\Availability;
use Lootwright\Domain\PoeCatalog\Character\Poe2CharacterCatalog;
use Lootwright\Domain\PoeCatalog\Character\ProgressionKind;
use Lootwright\Domain\PoeCatalog\Identifier\AscendancyId;
use Lootwright\Domain\PoeCatalog\Identifier\CharacterClassId;
use Lootwright\Domain\Shared\Game\GameEdition;
use PHPUnit\Framework\TestCase;

final class Poe2CharacterCatalogTest extends TestCase
{
    public function test_baseline_counts_and_availability_are_versioned(): void
    {
        $catalog = Poe2CharacterCatalog::current();
        self::assertCount(12, $catalog->classes);
        self::assertCount(8, array_filter($catalog->classes, static fn ($class): bool => $class->availability === Availability::Available));
        self::assertCount(4, array_filter($catalog->classes, static fn ($class): bool => $class->availability === Availability::Planned));
        $ascendancies = array_merge(...array_map(static fn ($class): array => $class->ascendancies, $catalog->classes));
        self::assertCount(22, array_filter($ascendancies, static fn ($asc): bool => $asc->type === ProgressionKind::Regular));
        self::assertCount(1, array_filter($ascendancies, static fn ($asc): bool => $asc->type === ProgressionKind::Alternate));
    }

    public function test_relationships_alternate_requirement_and_planned_classes_are_enforced(): void
    {
        $catalog = Poe2CharacterCatalog::current();
        self::assertTrue($catalog->supports('warrior', 'titan'));
        self::assertTrue($catalog->supports('ranger', 'deadeye'));
        self::assertTrue($catalog->supports('huntress', 'spirit-walker'));
        self::assertTrue($catalog->supports('witch', 'lich', 'abyssal-lich'));
        self::assertFalse($catalog->supports('witch', 'infernalist', 'abyssal-lich'));
        self::assertFalse($catalog->supports('witch', 'abyssal-lich'));
        foreach (['marauder', 'duelist', 'shadow', 'templar'] as $planned) {
            self::assertFalse($catalog->supports($planned, null));
        }
    }

    public function test_build_catalog_rejects_cross_game_and_invalid_pairs(): void
    {
        $poe1Deadeye = AscendancyId::from(GameEdition::Poe1, 'deadeye')->value();
        $poe2Ranger = CharacterClassId::from(GameEdition::Poe2, 'ranger')->value();
        self::assertTrue(BuildCatalog::create(GameEdition::Poe2, $poe2Ranger, $poe1Deadeye)->isFailure());

        $poe2Elementalist = AscendancyId::from(GameEdition::Poe2, 'elementalist')->value();
        $poe2Witch = CharacterClassId::from(GameEdition::Poe2, 'witch')->value();
        self::assertTrue(BuildCatalog::create(GameEdition::Poe2, $poe2Witch, $poe2Elementalist)->isFailure());
    }
}
