<?php

namespace App\Modules\BuildIntake;

use RuntimeException;

final class PobImportConflict extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The idempotency key was already used for a different import.');
    }
}
