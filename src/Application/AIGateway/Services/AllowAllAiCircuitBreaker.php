<?php

namespace Lootwright\Application\AIGateway\Services;

use Lootwright\Application\AIGateway\Ports\AiCircuitBreaker;

final class AllowAllAiCircuitBreaker implements AiCircuitBreaker
{
    public function permitsRequest(): bool
    {
        return true;
    }

    public function recordSuccess(): void {}

    public function recordFailure(): void {}
}
