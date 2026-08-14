<?php

namespace App\Modules\BuildIntake;

use Lootwright\Domain\Shared\Error\DomainError;
use RuntimeException;

final class PobImportRejected extends RuntimeException
{
    public function __construct(public readonly DomainError $domainError)
    {
        parent::__construct('The submitted build was rejected.');
    }
}
