<?php

namespace Lootwright\Application\Workflow\DTO;

use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Locale;

final readonly class SubmitBuildArtifactCommand
{
    public function __construct(
        public string $ownerId,
        public string $idempotencyKey,
        public GameEdition $edition,
        public Locale $locale,
        public string $artifactType,
        public string $artifact,
        public AnalysisParameters $parameters,
    ) {}
}
