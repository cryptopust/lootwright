<?php

namespace Lootwright\Application\Identity\Ports;

interface SecretGenerator
{
    public function hex(int $bytes): string;
}
