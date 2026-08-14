<?php

namespace Lootwright\Domain\UsageFunding;

use JsonSerializable;
use Lootwright\Domain\PolicyProvenance\CommercialUseStatus;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;

final readonly class FundingPolicy implements JsonSerializable
{
    private function __construct(
        public PermissionStatus $permission,
        public CommercialUseStatus $commercialUse,
    ) {}

    public static function disabled(): self
    {
        return new self(PermissionStatus::Denied, CommercialUseStatus::Unknown);
    }

    public function canAcceptFunds(): bool
    {
        return false;
    }

    /** @return array{permission: string, commercial_use: string, can_accept_funds: false} */
    public function jsonSerialize(): array
    {
        return [
            'permission' => $this->permission->value,
            'commercial_use' => $this->commercialUse->value,
            'can_accept_funds' => false,
        ];
    }
}
