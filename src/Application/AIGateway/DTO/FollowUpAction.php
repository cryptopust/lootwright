<?php

namespace Lootwright\Application\AIGateway\DTO;

use JsonSerializable;

final readonly class FollowUpAction implements JsonSerializable
{
    public function __construct(
        public string $action,
        public string $referenceId = '',
        public string $value = '',
        public int $confidenceBasisPoints = 0,
    ) {}

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'action' => $this->action,
            'reference_id' => $this->referenceId,
            'value' => $this->value,
            'confidence_basis_points' => $this->confidenceBasisPoints,
        ];
    }
}
