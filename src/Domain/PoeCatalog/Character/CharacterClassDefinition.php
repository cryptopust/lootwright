<?php

namespace Lootwright\Domain\PoeCatalog\Character;

use JsonSerializable;

final readonly class CharacterClassDefinition implements JsonSerializable
{
    /** @param list<AscendancyDefinition> $ascendancies */
    public function __construct(
        public string $id,
        public string $name,
        public int $order,
        public bool $active,
        public array $ascendancies,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'order' => $this->order,
            'active' => $this->active,
            'ascendancies' => $this->ascendancies,
        ];
    }
}
