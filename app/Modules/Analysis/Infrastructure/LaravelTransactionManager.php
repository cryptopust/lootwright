<?php

namespace App\Modules\Analysis\Infrastructure;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\Workflow\Ports\TransactionManager;

final class LaravelTransactionManager implements TransactionManager
{
    public function run(callable $operation): mixed
    {
        return DB::transaction(
            static fn (Connection $_connection): mixed => $operation(),
            3,
        );
    }
}
