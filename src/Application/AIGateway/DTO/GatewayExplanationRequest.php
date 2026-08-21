<?php

namespace Lootwright\Application\AIGateway\DTO;

use InvalidArgumentException;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Locale;

final readonly class GatewayExplanationRequest
{
    public GameEdition $edition;

    /**
     * @param  list<Finding>  $findings
     * @param  list<Recommendation>  $recommendations
     */
    public function __construct(
        public Locale $locale,
        public array $findings,
        public array $recommendations,
        public AiRequestContext $context,
    ) {
        $products = [...$findings, ...$recommendations];
        if ($products === []) {
            throw new InvalidArgumentException('An explanation requires deterministic products.');
        }
        $first = $products[0];
        $edition = $first instanceof Finding ? $first->gameEdition : $first->edition;
        $analysisId = $first->analysisId;
        foreach ($products as $product) {
            $productEdition = $product instanceof Finding ? $product->gameEdition : $product->edition;
            if ($productEdition !== $edition || ! $product->analysisId->equals($analysisId)) {
                throw new InvalidArgumentException('Explanation products must share one edition and analysis.');
            }
        }
        $this->edition = $edition;
    }
}
