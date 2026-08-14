<?php

namespace Lootwright\Domain\Shared\Identity;

abstract readonly class EditionScopedIdentifier extends EditionScopedValue
{
    final protected static function pattern(): string
    {
        return '/^[a-z][a-z0-9._-]{1,127}$/D';
    }
}
