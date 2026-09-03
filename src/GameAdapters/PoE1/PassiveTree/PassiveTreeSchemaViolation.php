<?php

namespace Lootwright\GameAdapters\PoE1\PassiveTree;

use DomainException;

final class PassiveTreeSchemaViolation extends DomainException
{
    public function __construct(public readonly string $reasonCode)
    {
        parent::__construct('The passive-tree export failed closed schema validation.');
    }
}
