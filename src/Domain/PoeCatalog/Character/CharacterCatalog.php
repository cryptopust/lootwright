<?php

namespace Lootwright\Domain\PoeCatalog\Character;

use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

interface CharacterCatalog extends JsonSerializable
{
    public function edition(): GameEdition;

    /** @return list<CharacterClassDefinition> */
    public function classes(): array;

    public function classById(string $classId): ?CharacterClassDefinition;

    public function supports(string $classId, ?string $ascendancyId, ?string $alternateAscendancyId = null, ?string $secondaryProgressionId = null): bool;
}
