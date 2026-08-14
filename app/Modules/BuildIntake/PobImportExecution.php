<?php

namespace App\Modules\BuildIntake;

use Lootwright\Domain\BuildIntake\Import\PobImportResult;

final readonly class PobImportExecution
{
    public function __construct(
        public PobImportResult $result,
        public ?StoredPobImport $storedImport,
    ) {}
}
