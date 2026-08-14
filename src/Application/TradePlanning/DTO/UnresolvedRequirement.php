<?php

namespace Lootwright\Application\TradePlanning\DTO;

use JsonSerializable;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;

final readonly class UnresolvedRequirement implements JsonSerializable
{
    public function __construct(
        public string $kind,
        public string $canonicalKey,
        public string $reason,
        public string $clarificationQuestion,
        public string $findingCode,
        public ExplanationTrace $trace,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'kind' => $this->kind,
            'canonical_key' => $this->canonicalKey,
            'reason' => $this->reason,
            'clarification_question' => $this->clarificationQuestion,
            'finding_code' => $this->findingCode,
            'trace' => $this->trace,
        ];
    }
}
