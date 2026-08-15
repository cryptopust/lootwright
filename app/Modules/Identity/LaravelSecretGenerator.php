<?php

namespace App\Modules\Identity;

use Lootwright\Application\Identity\Ports\SecretGenerator;

final class LaravelSecretGenerator implements SecretGenerator
{
    public function hex(int $bytes): string
    {
        return bin2hex(random_bytes(max(16, min($bytes, 64))));
    }
}
