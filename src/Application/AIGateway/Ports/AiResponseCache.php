<?php

namespace Lootwright\Application\AIGateway\Ports;

interface AiResponseCache
{
    /** @return array<string, mixed>|null */
    public function get(string $key, string $userHash): ?array;

    /** @param array<string, mixed> $value */
    public function put(string $key, string $userHash, array $value): void;
}
