<?php

namespace App\Modules\AI;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\AIGateway\Ports\AiResponseCache;

final readonly class LaravelAiResponseCache implements AiResponseCache
{
    public function __construct(private Repository $cache, private int $ttlSeconds) {}

    public function get(string $key, string $userHash): ?array
    {
        $value = $this->cache->get($this->cacheKey($key, $userHash));

        return is_array($value) ? $value : null;
    }

    public function put(string $key, string $userHash, array $value): void
    {
        $cacheKey = $this->cacheKey($key, $userHash);
        $this->cache->put($cacheKey, $value, $this->ttlSeconds);
        DB::table('ai_response_cache_keys')->updateOrInsert(
            ['user_hash' => $userHash, 'cache_key' => $cacheKey],
            ['expires_at' => now()->addSeconds($this->ttlSeconds), 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function purgeUser(string $userHash): void
    {
        foreach (DB::table('ai_response_cache_keys')->where('user_hash', $userHash)->pluck('cache_key') as $key) {
            if (is_string($key)) {
                $this->cache->forget($key);
            }
        }

        DB::table('ai_response_cache_keys')->where('user_hash', $userHash)->delete();
    }

    private function cacheKey(string $key, string $userHash): string
    {
        return 'ai:response:'.$userHash.':'.$key;
    }
}
