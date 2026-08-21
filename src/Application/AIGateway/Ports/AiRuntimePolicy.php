<?php

namespace Lootwright\Application\AIGateway\Ports;

interface AiRuntimePolicy
{
    public function permits(string $task): bool;
}
