<?php

namespace App\Modules\BuildIntake;

final readonly class StoredPobImport
{
    public function __construct(
        public string $id,
        public string $deletionToken,
        public string $expiresAt,
    ) {}
}
