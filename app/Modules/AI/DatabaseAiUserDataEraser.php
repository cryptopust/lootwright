<?php

namespace App\Modules\AI;

use Illuminate\Support\Facades\DB;

final readonly class DatabaseAiUserDataEraser
{
    public function __construct(private string $hmacKey, private ?LaravelAiResponseCache $cache = null) {}

    public function erase(string $ownerId): void
    {
        $userHash = hash_hmac('sha256', 'user:'.$ownerId, $this->hmacKey);

        $this->cache?->purgeUser($userHash);
        DB::table('ai_request_audits')->where('user_hash', $userHash)->delete();
        DB::table('ai_budget_counters')
            ->where('scope_type', 'user_daily')
            ->where('scope_key', $userHash)
            ->delete();
    }
}
