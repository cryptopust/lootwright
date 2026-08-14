<?php

namespace Lootwright\Application\TradePlanning\DTO;

use JsonSerializable;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;

final readonly class RecipeDependency implements JsonSerializable
{
    public function __construct(
        public string $slot,
        public string $reason,
        public string $findingCode,
        public ExplanationTrace $trace,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'slot' => $this->slot,
            'reason' => $this->reason,
            'finding_code' => $this->findingCode,
            'trace' => $this->trace,
        ];
    }
}
