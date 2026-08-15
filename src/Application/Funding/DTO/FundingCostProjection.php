<?php

namespace Lootwright\Application\Funding\DTO;

use JsonSerializable;

final readonly class FundingCostProjection implements JsonSerializable
{
    public function __construct(
        public string $scenario,
        public int $analysesPerMonth,
        public int $aiUsageRateBasisPoints,
        public int $estimatedAiCalls,
        public int $hostingMonthlyCents,
        public int $aiMonthlyCents,
        public int $totalMonthlyCents,
    ) {}

    /** @return array<string, int|string> */
    public function jsonSerialize(): array
    {
        return [
            'scenario' => $this->scenario,
            'analyses_per_month' => $this->analysesPerMonth,
            'ai_usage_rate_basis_points' => $this->aiUsageRateBasisPoints,
            'estimated_ai_calls' => $this->estimatedAiCalls,
            'hosting_monthly_cents' => $this->hostingMonthlyCents,
            'ai_monthly_cents' => $this->aiMonthlyCents,
            'total_monthly_cents' => $this->totalMonthlyCents,
        ];
    }
}
