<?php

namespace Lootwright\Domain\PoeCatalog\Character;

use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class Poe2CharacterCatalog implements CharacterCatalog, JsonSerializable
{
    public const GAME = 'poe2';

    public const VERSION = '0.5';

    public const DATA_VERSION = 'poe2-0.5-2026-08-20';

    public const VERIFIED_AT = '2026-08-20T00:00:00Z';

    /** @var list<array{name: string, url: string}> */
    public const SOURCES = [
        ['name' => 'Path of Exile 2 Wiki classes', 'url' => 'https://www.poe2wiki.net/wiki/Character_class'],
        ['name' => 'Path of Exile 2 Wiki Ascendancies', 'url' => 'https://www.poe2wiki.net/wiki/Category:Ascendancy_classes'],
        ['name' => 'Path of Exile 2 Wiki version history', 'url' => 'https://www.poe2wiki.net/wiki/Version_history'],
        ['name' => 'Path of Exile 2 Wiki 0.5.0', 'url' => 'https://www.poe2wiki.net/wiki/Version_0.5.0'],
        ['name' => 'Path of Exile 2 Wiki Abyssal Lich', 'url' => 'https://www.poe2wiki.net/wiki/Abyssal_Lich'],
    ];

    /** @param list<CharacterClassDefinition> $classes */
    private function __construct(public array $classes) {}

    public static function current(): self
    {
        return new self([
            self::available('warrior', 'Warrior', 10, [['titan', 'Titan'], ['warbringer', 'Warbringer'], ['smith-of-kitava', 'Smith of Kitava']]),
            self::available('ranger', 'Ranger', 20, [['deadeye', 'Deadeye'], ['pathfinder', 'Pathfinder']]),
            self::available('huntress', 'Huntress', 30, [['amazon', 'Amazon'], ['ritualist', 'Ritualist'], ['spirit-walker', 'Spirit Walker']]),
            self::available('witch', 'Witch', 40, [['infernalist', 'Infernalist'], ['blood-mage', 'Blood Mage'], ['lich', 'Lich']], [new AscendancyDefinition('abyssal-lich', 'Abyssal Lich', 40, Availability::Available, ProgressionKind::Alternate, 'lich')]),
            self::available('sorceress', 'Sorceress', 50, [['stormweaver', 'Stormweaver'], ['chronomancer', 'Chronomancer'], ['disciple-of-varashta', 'Disciple of Varashta']]),
            self::available('mercenary', 'Mercenary', 60, [['witchhunter', 'Witchhunter'], ['gemling-legionnaire', 'Gemling Legionnaire'], ['tactician', 'Tactician']]),
            self::available('monk', 'Monk', 70, [['invoker', 'Invoker'], ['acolyte-of-chayula', 'Acolyte of Chayula'], ['martial-artist', 'Martial Artist']]),
            self::available('druid', 'Druid', 80, [['shaman', 'Shaman'], ['oracle', 'Oracle']]),
            self::planned('marauder', 'Marauder', 90),
            self::planned('duelist', 'Duelist', 100),
            self::planned('shadow', 'Shadow', 110),
            self::planned('templar', 'Templar', 120),
        ]);
    }

    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    /** @return list<CharacterClassDefinition> */
    public function classes(): array
    {
        return $this->classes;
    }

    public function classById(string $classId): ?CharacterClassDefinition
    {
        foreach ($this->classes as $class) {
            if ($class->id === $classId) {
                return $class;
            }
        }

        return null;
    }

    public function supports(string $classId, ?string $ascendancyId, ?string $alternateAscendancyId = null, ?string $secondaryProgressionId = null): bool
    {
        $class = $this->classById($classId);
        if ($class === null || $class->availability !== Availability::Available || $secondaryProgressionId !== null) {
            return false;
        }
        if ($ascendancyId === null || $ascendancyId === '') {
            return $alternateAscendancyId === null || $alternateAscendancyId === '';
        }

        $regular = null;
        foreach ($class->ascendancies as $ascendancy) {
            if ($ascendancy->id === $ascendancyId && $ascendancy->type === ProgressionKind::Regular && $ascendancy->availability === Availability::Available) {
                $regular = $ascendancy;
            }
        }
        if ($regular === null) {
            return false;
        }
        if ($alternateAscendancyId === null || $alternateAscendancyId === '') {
            return true;
        }
        foreach ($class->ascendancies as $ascendancy) {
            if ($ascendancy->id === $alternateAscendancyId && $ascendancy->type === ProgressionKind::Alternate && $ascendancy->availability === Availability::Available && $ascendancy->requiresBaseAscendancy === $regular->id) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return ['game' => self::GAME, 'version' => self::VERSION, 'patch' => self::VERSION, 'early_access' => true, 'data_version' => self::DATA_VERSION, 'verified_at' => self::VERIFIED_AT, 'source' => self::SOURCES[0]['url'], 'sources' => self::SOURCES, 'classes' => $this->classes];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $regular
     * @param  list<AscendancyDefinition>  $extra
     */
    private static function available(string $id, string $name, int $order, array $regular, array $extra = []): CharacterClassDefinition
    {
        $ascendancies = array_map(static fn (array $asc, int $index): AscendancyDefinition => new AscendancyDefinition($asc[0], $asc[1], ($index + 1) * 10, Availability::Available), $regular, array_keys($regular));

        return new CharacterClassDefinition($id, $name, $order, Availability::Available, [...$ascendancies, ...$extra]);
    }

    private static function planned(string $id, string $name, int $order): CharacterClassDefinition
    {
        return new CharacterClassDefinition($id, $name, $order, Availability::Planned, []);
    }
}
