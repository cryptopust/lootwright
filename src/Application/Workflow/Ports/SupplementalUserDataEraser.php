<?php

namespace Lootwright\Application\Workflow\Ports;

interface SupplementalUserDataEraser
{
    public function erase(string $ownerId): void;
}
