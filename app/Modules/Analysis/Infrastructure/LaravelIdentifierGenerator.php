<?php

namespace App\Modules\Analysis\Infrastructure;

use Illuminate\Support\Str;
use Lootwright\Application\Workflow\Ports\IdentifierGenerator;

final class LaravelIdentifierGenerator implements IdentifierGenerator
{
    public function uuid7(): string
    {
        return (string) Str::uuid7();
    }
}
