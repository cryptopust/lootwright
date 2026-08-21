<?php

namespace Lootwright\Application\AIGateway\Ports;

use Lootwright\Application\AIGateway\DTO\AiGatewayOutcome;
use Lootwright\Application\AIGateway\DTO\NaturalLanguageIntentRequest;

interface IntentInterpreter
{
    public function extractIntent(NaturalLanguageIntentRequest $request): AiGatewayOutcome;
}
