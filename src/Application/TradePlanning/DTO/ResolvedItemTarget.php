<?php

namespace Lootwright\Application\TradePlanning\DTO;

use JsonSerializable;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;

final readonly class ResolvedItemTarget implements JsonSerializable
{
    public function __construct(
        public string $code,
        public string $exactCategoryLabel,
        public ?string $exactBaseFamilyLabel,
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
            'exact_category_label' => $this->exactCategoryLabel,
            'exact_base_family_label' => $this->exactBaseFamilyLabel,
            'rule_key' => $this->ruleKey,
            'confidence_basis_points' => $this->confidenceBasisPoints,
            'finding_code' => $this->findingCode,
            'trace' => $this->trace,
        ];
    }
}
