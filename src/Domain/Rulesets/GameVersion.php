<?php

namespace Lootwright\Domain\Rulesets;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\PatchVersion;

final readonly class GameVersion implements JsonSerializable
{
    public function __construct(
        public GameEdition $edition,
        public PatchVersion $patch,
    ) {
        if (! $patch->belongsTo($edition)) {
            throw new InvalidArgumentException('A game version patch must belong to its edition.');
        }
    }

    /** @return array{edition: string, patch: string} */
    public function jsonSerialize(): array
    {
        return ['edition' => $this->edition->value, 'patch' => $this->patch->value];
    }
}
