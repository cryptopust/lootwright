<?php

namespace Lootwright\Application\Market;

use JsonSerializable;
use Lootwright\Domain\Market\MarketObservation;

final readonly class MarketEstimate implements JsonSerializable
{
    public function __construct(
        public MarketEstimateStatus $status,
        public ?MarketObservation $observation = null,
        public string $reason = '',
    ) {}

    public function isCurrent(): bool
    {
        return $this->status === MarketEstimateStatus::Live && $this->observation !== null;
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status->value,
            'observation' => $this->observation,
            'reason' => $this->reason,
        ];
    }
}
