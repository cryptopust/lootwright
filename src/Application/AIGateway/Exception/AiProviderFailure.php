<?php

namespace Lootwright\Application\AIGateway\Exception;

use RuntimeException;

final class AiProviderFailure extends RuntimeException
{
    public function __construct(
        public readonly string $failureCode,
        public readonly bool $transient,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct("AI provider request failed: {$failureCode}.");
    }
}
