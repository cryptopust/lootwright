<?php

namespace Lootwright\Domain\BuildIntake\Ports;

use DOMDocument;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\Shared\Error\DomainResult;

interface PobBuildParser
{
    public function rootElement(): string;

    public function parse(DOMDocument $document, string $inputChecksum, ImportLimits $limits): DomainResult;
}
