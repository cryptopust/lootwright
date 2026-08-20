<?php

namespace Lootwright\Domain\PoeCatalog\Character;

use JsonSerializable;

final readonly class Poe1CharacterCatalog implements JsonSerializable
{
    public const GAME = 'poe1';

    public const PATCH = '3.28';

    public const DATA_VERSION = 'poe1-3.28-2026-08-20';

    public const SOURCE_URL = 'https://www.poewiki.net/wiki/Ascendancy_class';

    public const PATCH_SOURCE_URL = 'https://www.poewiki.net/wiki/Version_3.28.0';

    public const VERIFIED_AT = '2026-08-20T00:00:00Z';

    /** @param list<CharacterClassDefinition> $classes */
    private function __construct(public array $classes) {}

    public static function current(): self
    {
        return new self([
            self::class('duelist', 'Duelist', 10, [['slayer', 'Slayer'], ['gladiator', 'Gladiator'], ['champion', 'Champion']]),
            self::class('shadow', 'Shadow', 20, [['assassin', 'Assassin'], ['saboteur', 'Saboteur'], ['trickster', 'Trickster']]),
            self::class('marauder', 'Marauder', 30, [['juggernaut', 'Juggernaut'], ['berserker', 'Berserker'], ['chieftain', 'Chieftain']]),
            self::class('witch', 'Witch', 40, [['necromancer', 'Necromancer'], ['elementalist', 'Elementalist'], ['occultist', 'Occultist']]),
            self::class('ranger', 'Ranger', 50, [['deadeye', 'Deadeye'], ['warden', 'Warden'], ['pathfinder', 'Pathfinder']]),
            self::class('templar', 'Templar', 60, [['inquisitor', 'Inquisitor'], ['hierophant', 'Hierophant'], ['guardian', 'Guardian']]),
            self::class('scion', 'Scion', 70, [['ascendant', 'Ascendant'], ['reliquarian', 'Reliquarian']]),
        ]);
    }

    public function supports(string $classId, ?string $ascendancyId): bool
    {
        $class = $this->classById($classId);
        if ($class === null || ! $class->active) {
            return false;
        }

        if ($ascendancyId === null || $ascendancyId === '') {
            return true;
        }

        foreach ($class->ascendancies as $ascendancy) {
            if ($ascendancy->id === $ascendancyId && $ascendancy->active && $ascendancy->kind === ProgressionKind::Ascendancy) {
                return true;
            }
        }

        return false;
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

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'game' => self::GAME,
            'patch' => self::PATCH,
            'data_version' => self::DATA_VERSION,
            'verified_at' => self::VERIFIED_AT,
            'source' => self::SOURCE_URL,
            'patch_source' => self::PATCH_SOURCE_URL,
            'classes' => $this->classes,
        ];
    }

    /** @param list<array{0: string, 1: string}> $ascendancies */
    private static function class(string $id, string $name, int $order, array $ascendancies): CharacterClassDefinition
    {
        return new CharacterClassDefinition($id, $name, $order, true, array_map(
            static fn (array $ascendancy, int $index): AscendancyDefinition => new AscendancyDefinition($ascendancy[0], $ascendancy[1], ($index + 1) * 10, true),
            $ascendancies,
            array_keys($ascendancies),
        ));
    }
}
