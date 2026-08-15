<?php

namespace Lootwright\Application\AIGateway\Ports;

use Lootwright\Application\AIGateway\DTO\ExplanationBundle;

interface AnalysisExplanationRepository
{
    public function storeForOwner(
        string $analysisId,
        string $ownerId,
        ExplanationBundle $bundle,
        string $status,
    ): bool;
}
