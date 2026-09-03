<?php

namespace Lootwright\Application\GameData\Ports;

use Lootwright\Application\GameData\DTO\CanonicalDataConflict;

interface CanonicalDataConflictRecorder
{
    public function record(CanonicalDataConflict $conflict, ?string $rulesetVersionId = null): void;
}
