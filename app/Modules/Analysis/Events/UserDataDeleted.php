<?php

namespace App\Modules\Analysis\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class UserDataDeleted implements ShouldDispatchAfterCommit
{
    public function __construct(
        public int $artifactsDeleted,
        public int $analysesDeleted,
    ) {}
}
