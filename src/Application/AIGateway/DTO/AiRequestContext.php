<?php

namespace Lootwright\Application\AIGateway\DTO;

use InvalidArgumentException;

final readonly class AiRequestContext
{
    public function __construct(
        public string $userHash,
        public string $ipHash,
        public bool $userOptedIn,
        public bool $cachePermitted = false,
    ) {
        foreach ([$userHash, $ipHash] as $hash) {
            if (preg_match('/^[a-f0-9]{64}$/D', $hash) !== 1) {
                throw new InvalidArgumentException('AI requester identifiers must be non-identifying SHA-256 hashes.');
            }
        }
    }
}
