<?php

namespace Lootwright\Domain\BuildIntake\Ports;

use Lootwright\Domain\BuildIntake\Import\BuildInputType;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

interface BuildImporter
{
    public function edition(): GameEdition;

    public function supports(BuildInputType $inputType): bool;

    public function import(string $input, BuildInputType $inputType, ImportLimits $limits): DomainResult;
}
