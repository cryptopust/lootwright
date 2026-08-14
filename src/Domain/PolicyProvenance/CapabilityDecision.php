<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;

final readonly class CapabilityDecision implements JsonSerializable
{
    public function __construct(
        public Capability $capability,
        public PermissionStatus $permission,
        public CommercialUseStatus $commercialUse,
    ) {}

    public function isDenied(): bool
    {
        return $this->permission !== PermissionStatus::Allowed
            || $this->commercialUse === CommercialUseStatus::Unknown
            || $this->commercialUse === CommercialUseStatus::Prohibited;
    }

    /** @return array{capability: string, permission: string, commercial_use: string} */
    public function jsonSerialize(): array
    {
        return [
            'capability' => $this->capability->value,
            'permission' => $this->permission->value,
            'commercial_use' => $this->commercialUse->value,
        ];
    }
}
