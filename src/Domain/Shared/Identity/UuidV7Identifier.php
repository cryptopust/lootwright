<?php

namespace Lootwright\Domain\Shared\Identity;

abstract readonly class UuidV7Identifier extends EditionScopedValue
{
    final protected static function pattern(): string
    {
        return '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D';
    }

    protected static function invalidMessage(): string
    {
        return 'The identifier must be a canonical lowercase UUID version 7.';
    }
}
