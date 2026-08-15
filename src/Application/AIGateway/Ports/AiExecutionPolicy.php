<?php

namespace Lootwright\Application\AIGateway\Ports;

interface AiExecutionPolicy
{
    public function permits(string $task): bool;
}
