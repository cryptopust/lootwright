<?php

namespace Lootwright\Application\AIGateway\DTO;

use Lootwright\Domain\BuildIntake\Intent\BuildIntent;

final readonly class BuildIntentResolution
{
    public function __construct(
        public ?BuildIntent $intent,
        public ?ClarificationSet $clarifications,
        public string $source,
    ) {}
}
