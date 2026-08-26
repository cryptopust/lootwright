<?php

namespace Lootwright\Application\Market;

use Lootwright\Domain\Recommendations\MarketPriceEvidence;
use Lootwright\Domain\Recommendations\UpgradeCandidate;

interface MarketEvidenceResolver
{
    public function resolve(UpgradeCandidate $candidate): ?MarketPriceEvidence;
}
