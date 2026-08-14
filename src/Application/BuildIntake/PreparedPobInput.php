<?php

namespace Lootwright\Application\BuildIntake;

use DOMDocument;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class PreparedPobInput
{
    public function __construct(
        public DOMDocument $document,
        public string $rootElement,
        public string $checksumSha256,
    ) {}

    public function edition(): GameEdition
    {
        return $this->rootElement === 'PathOfBuilding'
            ? GameEdition::Poe1
            : GameEdition::Poe2;
    }
}
