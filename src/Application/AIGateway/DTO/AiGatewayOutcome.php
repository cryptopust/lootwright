<?php

namespace Lootwright\Application\AIGateway\DTO;

use JsonSerializable;

final readonly class AiGatewayOutcome implements JsonSerializable
{
    /** @param array<string, bool|int|string> $metadata */
    public function __construct(
        public string $status,
        public BuildIntentCandidate|ClarificationSet|ExplanationBundle $value,
        public array $metadata = [],
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return ['status' => $this->status, 'value' => $this->value, 'metadata' => $this->metadata];
    }
}
