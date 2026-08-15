<?php

namespace Lootwright\Application\Funding\DTO;

use JsonSerializable;

final readonly class FundingStatus implements JsonSerializable
{
    /**
     * @param  array<string, bool>  $activationRequirements
     * @param  list<FundingCostProjection>  $costProjections
     */
    public function __construct(
        public bool $requestedEnabled,
        public bool $enabled,
        public string $policyDecision,
        public array $activationRequirements,
        public string $currency,
        public string $pricingModel,
        public string $pricingReviewedOn,
        public string $pricingSource,
        public array $costProjections,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'requested_enabled' => $this->requestedEnabled,
            'enabled' => $this->enabled,
            'accepting_funds' => false,
            'policy_decision' => $this->policyDecision,
            'activation_requirements' => $this->activationRequirements,
            'currency' => $this->currency,
            'pricing_model' => $this->pricingModel,
            'pricing_reviewed_on' => $this->pricingReviewedOn,
            'pricing_source' => $this->pricingSource,
            'cost_projections' => array_map(
                static fn (FundingCostProjection $projection): array => $projection->jsonSerialize(),
                $this->costProjections,
            ),
        ];
    }
}
