<?php

namespace Lootwright\Application\Workflow\DTO;

use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class ArtifactRecord
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $analysisId,
        public GameEdition $edition,
        public string $artifactType,
        public string $blobKey,
        public string $artifactHashSha256,
        public AnalysisState $state,
        public ?string $adapterKey = null,
        public ?string $parserVersion = null,
        public ?string $normalizedSnapshot = null,
        public ?string $normalizedHashSha256 = null,
        public ?string $patchVersion = null,
        public ?string $league = null,
    ) {}
}
