<?php

namespace Lootwright\Application\Rulesets\DTO;

use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class RulesetActivation
{
    public function __construct(
        public string $activationId,
        public GameEdition $edition,
        public string $patch,
        public ?string $league,
        public string $parserVersion,
        public string $rulesetVersionId,
        public ?string $previousRulesetVersionId,
    ) {}
}
