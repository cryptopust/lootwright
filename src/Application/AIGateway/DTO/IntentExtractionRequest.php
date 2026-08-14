<?php

namespace Lootwright\Application\AIGateway\DTO;

use Lootwright\Domain\BuildIntake\Intent\PlayerGoal;
use Lootwright\Domain\Shared\Value\Locale;

final readonly class IntentExtractionRequest
{
    public function __construct(
        public PlayerGoal $goal,
        public Locale $locale,
    ) {}
}
