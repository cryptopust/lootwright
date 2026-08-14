<?php

namespace Lootwright\Application\Workflow\Ports;

interface TransactionManager
{
    public function run(callable $operation): mixed;
}
