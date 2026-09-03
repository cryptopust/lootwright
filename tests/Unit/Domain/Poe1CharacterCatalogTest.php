<?php

namespace Tests\Unit\Domain;

use Lootwright\Domain\PoeCatalog\BuildCatalog;
use Lootwright\Domain\PoeCatalog\Character\Poe1CharacterCatalog;
use Lootwright\Domain\PoeCatalog\Identifier\AscendancyId;
use Lootwright\Domain\PoeCatalog\Identifier\CharacterClassId;
use Lootwright\Domain\Shared\Game\GameEdition;
use PHPUnit\Framework\TestCase;

final class Poe1CharacterCatalogTest extends TestCase
{
    public function test_current_catalog_has_seven_classes_and_twenty_normal_ascendancies(): void
    {
        $catalog = Poe1CharacterCatalog::current();
        self::assertCount(7, $catalog->classes);
        self::assertSame(20, array_sum(array_map(static fn ($class): int => count($class->ascendancies), $catalog->classes)));
    }

    public function test_current_relationships_and_retired_or_secondary_names_are_enforced(): void
    {
        $catalog = Poe1CharacterCatalog::current();
        self::assertTrue($catalog->supports('ranger', 'warden'));
        self::assertFalse($catalog->supports('witch', 'warden'));
        self::assertTrue($catalog->supports('scion', 'reliquarian'));
        self::assertFalse($catalog->supports('ranger', 'reliquarian'));
        self::assertFalse($catalog->supports('ranger', 'raider'));
        self::assertFalse($catalog->supports('scion', 'bloodline'));
        self::assertTrue($catalog->supports('witch', null));
    }

    public function test_build_catalog_rejects_an_invalid_class_ascendancy_pair(): void
    {
        $class = CharacterClassId::from(GameEdition::Poe1, 'witch')->value();
        $ascendancy = AscendancyId::from(GameEdition::Poe1, 'warden')->value();

        self::assertTrue(BuildCatalog::create(GameEdition::Poe1, $class, $ascendancy)->isFailure());
    }
}
