<?php

namespace Lootwright\Application\ExternalSources\Ports;

use Lootwright\Domain\Shared\Game\GameEdition;

interface SourceUpdateObserver
{
    public function latestChecksum(string $sourceCode, GameEdition $edition): ?string;

    public function record(
        string $sourceCode,
        GameEdition $edition,
        string $sourceVersion,
        ?string $previousChecksumSha256,
        ?string $observedChecksumSha256,
        string $status,
        ?string $failureCode = null,
    ): void;
}
