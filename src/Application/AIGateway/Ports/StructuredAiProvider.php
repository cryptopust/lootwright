<?php

namespace Lootwright\Application\AIGateway\Ports;

use Lootwright\Application\AIGateway\DTO\StructuredAiRequest;
use Lootwright\Application\AIGateway\DTO\StructuredAiResponse;

interface StructuredAiProvider
{
    public function respond(StructuredAiRequest $request): StructuredAiResponse;
}
