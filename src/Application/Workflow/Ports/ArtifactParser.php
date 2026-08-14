<?php

namespace Lootwright\Application\Workflow\Ports;

use Lootwright\Application\Workflow\DTO\ParsedArtifact;
use Lootwright\Domain\Shared\Game\GameEdition;

interface ArtifactParser
{
    public function parse(string $artifactType, string $contents, GameEdition $expectedEdition): ParsedArtifact;
}
