<?php

namespace Lootwright\Domain\PoeCatalog\Character;

use JsonSerializable;

final readonly class AscendancyDefinition implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public int $order,
        public bool $active,
        public ProgressionKind $kind = ProgressionKind::Ascendancy,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'order' => $this->order,
            'active' => $this->active,
            'kind' => $this->kind->value,
        ];
    }
}
