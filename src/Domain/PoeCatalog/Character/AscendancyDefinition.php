<?php

namespace Lootwright\Domain\PoeCatalog\Character;

use JsonSerializable;

final readonly class AscendancyDefinition implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public int $order,
        public Availability $availability,
        public ProgressionKind $type = ProgressionKind::Regular,
        public ?string $requiresBaseAscendancy = null,
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
            'type' => $this->type->value,
            'kind' => $this->type->value,
            'requires_base_ascendancy' => $this->requiresBaseAscendancy,
        ];
    }
}
