<?php

namespace Lootwright\Application\Identity\Ports;

use Lootwright\Application\Identity\DTO\PrivacySessionCredential;

interface PrivacySessionRepository
{
    public function create(string $id, string $secret, int $ttlHours): PrivacySessionCredential;

    public function resolve(string $credential): ?string;

    public function markDeleted(string $credential): void;
}
