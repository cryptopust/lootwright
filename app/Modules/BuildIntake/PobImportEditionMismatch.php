<?php

namespace App\Modules\BuildIntake;

use Lootwright\Domain\Shared\Game\GameEdition;
use RuntimeException;

final class PobImportEditionMismatch extends RuntimeException
{
    public function __construct(
        public readonly GameEdition $expected,
        public readonly GameEdition $detected,
    ) {
        parent::__construct('The detected build edition differs from the requested edition.');
    }
}
