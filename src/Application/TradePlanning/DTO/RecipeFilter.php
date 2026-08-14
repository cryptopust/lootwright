<?php

namespace Lootwright\Application\TradePlanning\DTO;

use JsonSerializable;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;

final readonly class RecipeFilter implements JsonSerializable
{
    public function __construct(
        public string $canonicalModifierId,
        public string $exactLabel,
        public ?NumericRange $range,
        public ?int $weight,
        public string $reason,
        public string $findingCode,
        public ExplanationTrace $trace,
        public string $ruleKey,
        public int $confidenceBasisPoints,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'canonical_modifier_id' => $this->canonicalModifierId,
            'exact_label' => $this->exactLabel,
            'range' => $this->range,
            'weight' => $this->weight,
            'reason' => $this->reason,
            'finding_code' => $this->findingCode,
            'trace' => $this->trace,
            'rule_key' => $this->ruleKey,
            'confidence_basis_points' => $this->confidenceBasisPoints,
        ];
    }
}
