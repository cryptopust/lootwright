<?php

namespace Lootwright\Application\AIGateway\DTO;

use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Shared\Value\Locale;

final readonly class GatewayExplanationRequest
{
    /**
     * @param  list<Finding>  $findings
     * @param  list<Recommendation>  $recommendations
     */
    public function __construct(
        public Locale $locale,
        public array $findings,
        public array $recommendations,
        public AiRequestContext $context,
    ) {}
}
