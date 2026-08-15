<?php

namespace App\Modules\Analysis\Infrastructure;

use App\Modules\AI\DatabaseAiUserDataEraser;
use App\Modules\BuildIntake\PobImportStore;
use Lootwright\Application\Workflow\Ports\SupplementalUserDataEraser;

final readonly class CompositeSupplementalUserDataEraser implements SupplementalUserDataEraser
{
    public function __construct(
        private PobImportStore $pobImports,
        private DatabaseAiUserDataEraser $aiData,
    ) {}

    public function erase(string $ownerId): void
    {
        $this->pobImports->erase($ownerId);
        $this->aiData->erase($ownerId);
    }
}
