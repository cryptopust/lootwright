<?php

namespace Lootwright\Application\AIGateway\Ports;

interface AiCircuitBreaker
{
    public function permitsRequest(): bool;

    public function recordSuccess(): void;

    public function recordFailure(): void;
}
