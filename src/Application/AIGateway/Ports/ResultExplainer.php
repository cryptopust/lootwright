<?php

namespace Lootwright\Application\AIGateway\Ports;

use Lootwright\Application\AIGateway\DTO\AnalysisExplanationRequest;
use Lootwright\Domain\Shared\Error\DomainResult;

interface ResultExplainer
{
    public function explain(AnalysisExplanationRequest $request): DomainResult;
}
