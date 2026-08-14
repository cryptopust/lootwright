<?php

namespace App\Modules\BuildIntake;

final class PobImportIdempotency
{
    private function __construct() {}

    public static function isValid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^[A-Za-z0-9._:-]{32,128}$/D', $value) === 1;
    }
}
