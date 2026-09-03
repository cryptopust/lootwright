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
        public Availability $availability,
        public array $ascendancies,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'order' => $this->order,
            'availability' => $this->availability->value,
            'active' => $this->availability === Availability::Available,
            'ascendancies' => $this->ascendancies,
        ];
    }
}
