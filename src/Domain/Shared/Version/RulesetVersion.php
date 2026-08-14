<?php

namespace Lootwright\Domain\Shared\Version;

use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Identity\EditionScopedValue;

final readonly class RulesetVersion extends EditionScopedValue
{
    protected static function pattern(): string
    {
        return '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9a-z.-]+)?$/D';
    }

    protected static function errorCode(): DomainErrorCode
    {
        return DomainErrorCode::InvalidVersion;
    }
}
