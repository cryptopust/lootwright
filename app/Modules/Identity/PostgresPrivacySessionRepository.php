<?php

namespace App\Modules\Identity;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\Identity\DTO\PrivacySessionCredential;
use Lootwright\Application\Identity\Ports\PrivacySessionRepository;

final class PostgresPrivacySessionRepository implements PrivacySessionRepository
{
    public function create(string $id, string $secret, int $ttlHours): PrivacySessionCredential
    {
        $expires = CarbonImmutable::now('UTC')->addHours($ttlHours);
        DB::table('privacy_sessions')->insert([
            'id' => $id,
            'access_token_hash_sha256' => hash('sha256', $secret),
            'status' => 'active',
            'expires_at' => $expires,
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return new PrivacySessionCredential($id, $id.'.'.$secret, $expires->format('Y-m-d\TH:i:s\Z'));
    }

    public function resolve(string $credential): ?string
    {
        [$id, $secret] = $this->parts($credential);

        if ($id === null || $secret === null) {
            return null;
        }

        $row = DB::table('privacy_sessions')
            ->where('id', $id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($row === null || ! is_string($row->access_token_hash_sha256)
            || ! hash_equals($row->access_token_hash_sha256, hash('sha256', $secret))
        ) {
            return null;
        }

        DB::table('privacy_sessions')->where('id', $id)->update(['last_seen_at' => now(), 'updated_at' => now()]);

        return 'privacy-session:'.$id;
    }

    public function markDeleted(string $credential): void
    {
        [$id, $secret] = $this->parts($credential);

        if ($id === null || $secret === null) {
            return;
        }

        DB::table('privacy_sessions')
            ->where('id', $id)
            ->where('access_token_hash_sha256', hash('sha256', $secret))
            ->update([
                'status' => 'deleted',
                'deletion_requested_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /** @return array{?string, ?string} */
    private function parts(string $credential): array
    {
        if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})\.([0-9a-f]{64})$/D', $credential, $matches) !== 1) {
            return [null, null];
        }

        return [$matches[1], $matches[2]];
    }
}
