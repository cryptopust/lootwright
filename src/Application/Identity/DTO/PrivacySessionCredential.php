<?php

namespace Lootwright\Application\Identity\DTO;

final readonly class PrivacySessionCredential
{
    public function __construct(
        public string $id,
        public string $token,
        public string $expiresAt,
    ) {}
}
