<?php

namespace Lootwright\Domain\BuildIntake\Ports;

use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

interface ItemTextBuildImporter
{
    public function edition(): GameEdition;

    public function import(string $input, ImportLimits $limits): DomainResult;
}
