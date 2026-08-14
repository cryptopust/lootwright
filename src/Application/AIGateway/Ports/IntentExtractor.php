<?php

namespace Lootwright\Application\AIGateway\Ports;

use Lootwright\Application\AIGateway\DTO\IntentExtractionRequest;
use Lootwright\Domain\Shared\Error\DomainResult;

interface IntentExtractor
{
    public function extract(IntentExtractionRequest $request): DomainResult;
}
