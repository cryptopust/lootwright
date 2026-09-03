<?php

namespace Lootwright\Application\AIGateway\Ports;

use Lootwright\Application\AIGateway\DTO\AiGatewayOutcome;
use Lootwright\Application\AIGateway\DTO\GatewayExplanationRequest;
use Lootwright\Application\AIGateway\DTO\NaturalLanguageIntentRequest;

interface AiGateway extends IntentInterpreter, RecommendationExplainer
{
    public function extractIntent(NaturalLanguageIntentRequest $request): AiGatewayOutcome;

    public function explain(GatewayExplanationRequest $request): AiGatewayOutcome;
}
