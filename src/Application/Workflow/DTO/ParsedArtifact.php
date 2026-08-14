<?php

namespace Lootwright\Application\Workflow\DTO;

use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class ParsedArtifact
{
    /** @param list<array{code: string, question: string}> $clarifications */
    public function __construct(
        public GameEdition $edition,
        public string $adapterKey,
        public string $parserVersion,
        public string $normalizedSnapshot,
        public string $normalizedHashSha256,
        public ?string $patchVersion,
        public ?string $league,
        public array $clarifications = [],
    ) {}
}
