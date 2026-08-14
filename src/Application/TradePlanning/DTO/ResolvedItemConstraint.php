<?php

namespace Lootwright\Application\TradePlanning\DTO;

use JsonSerializable;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;

final readonly class ResolvedItemConstraint implements JsonSerializable
{
    public function __construct(
        public string $code,
        public string $exactLabel,
        public string $ruleKey,
        public int $confidenceBasisPoints,
        public string $findingCode,
        public ExplanationTrace $trace,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'exact_label' => $this->exactLabel,
            'rule_key' => $this->ruleKey,
            'confidence_basis_points' => $this->confidenceBasisPoints,
            'finding_code' => $this->findingCode,
            'trace' => $this->trace,
        ];
    }
}
