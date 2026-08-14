<?php

namespace Lootwright\Application\TradePlanning\Exception;

use RuntimeException;

final class ManualRecipeGenerationFailed extends RuntimeException
{
    public function __construct(public readonly string $failureCode, string $message)
    {
        parent::__construct($message);
    }
}
