<?php

namespace App\Modules\ExternalSources\PoeNinja;

final class PoeNinjaFailure extends \RuntimeException
{
    public function __construct(public readonly string $failureCode, public readonly bool $retryable)
    {
        parent::__construct($failureCode);
    }
}
