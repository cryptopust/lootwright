<?php

namespace Lootwright\Application\AIGateway\Services;

use Lootwright\Application\AIGateway\Ports\AiRuntimePolicy;

final class AllowAllAiRuntimePolicy implements AiRuntimePolicy
{
    public function permits(string $task): bool
    {
        return in_array($task, ['intent', 'clarification', 'explanation'], true);
    }
}
