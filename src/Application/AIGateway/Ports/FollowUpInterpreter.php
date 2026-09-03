<?php

namespace Lootwright\Application\AIGateway\Ports;

use Lootwright\Application\AIGateway\DTO\FollowUpOutcome;
use Lootwright\Application\AIGateway\DTO\FollowUpQuestionRequest;

interface FollowUpInterpreter
{
    public function interpretFollowUp(FollowUpQuestionRequest $request): FollowUpOutcome;
}
