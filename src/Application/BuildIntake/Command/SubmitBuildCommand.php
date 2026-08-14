<?php

namespace Lootwright\Application\BuildIntake\Command;

use Lootwright\Domain\Shared\Game\GameScope;
use Lootwright\Domain\Shared\Value\Locale;

final readonly class SubmitBuildCommand
{
    public function __construct(
        public GameScope $scope,
        public Locale $locale,
        public string $submittedText,
    ) {}
}
