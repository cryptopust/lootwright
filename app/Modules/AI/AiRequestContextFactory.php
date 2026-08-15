<?php

namespace App\Modules\AI;

use Lootwright\Application\AIGateway\DTO\AiRequestContext;

final readonly class AiRequestContextFactory
{
    public function __construct(private string $hmacKey) {}

    public function make(string $ownerId, string $ipAddress, bool $optedIn, bool $cachePermitted): AiRequestContext
    {
        return new AiRequestContext(
            hash_hmac('sha256', 'user:'.$ownerId, $this->hmacKey),
            hash_hmac('sha256', 'ip:'.$ipAddress, $this->hmacKey),
            $optedIn,
            $cachePermitted,
        );
    }
}
