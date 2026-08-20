<?php

namespace App\Modules\Administration;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AdminAuditLogger
{
    private const DENIED_KEYS = ['password', 'token', 'pob', 'artifact', 'item_text', 'prompt', 'response', 'session', 'cookie', 'secret', 'ip'];

    /** @param array<string, scalar|null> $metadata */
    public function record(User $actor, string $action, string $reason, ?User $target = null, array $metadata = []): void
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw new InvalidArgumentException('Audit actions require a bounded reason.');
        }
        foreach (array_keys($metadata) as $key) {
            if (in_array(mb_strtolower($key), self::DENIED_KEYS, true)) {
                throw new InvalidArgumentException('Sensitive audit metadata is prohibited.');
            }
        }

        DB::table('admin_audit_logs')->insert([
            'id' => (string) Str::uuid7(),
            'actor_user_id' => $actor->id,
            'target_user_id' => $target?->id,
            'action' => $action,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'reason' => $reason,
            'correlation_id' => (string) Str::uuid7(),
            'created_at' => now(),
        ]);
    }
}
