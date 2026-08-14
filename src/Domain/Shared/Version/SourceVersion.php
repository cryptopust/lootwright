<?php

namespace Lootwright\Domain\Shared\Version;

use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Identity\EditionScopedValue;

final readonly class SourceVersion extends EditionScopedValue
{
    protected static function pattern(): string
    {
        return '/^[0-9a-z][0-9a-z._-]{0,127}$/D';
    }

    protected static function errorCode(): DomainErrorCode
    {
        return DomainErrorCode::InvalidVersion;
    }
}
