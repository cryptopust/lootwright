<?php

namespace Lootwright\Application\AIGateway\DTO;

use JsonSerializable;

final readonly class FollowUpOutcome implements JsonSerializable
{
    public function __construct(
        public string $status,
        public ?FollowUpAction $action,
        public string $message,
        /** @var array<string,bool|int|string> */
        public array $metadata = [],
    ) {}

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return ['status' => $this->status, 'action' => $this->action, 'message' => $this->message, 'metadata' => $this->metadata];
    }
}
