<?php

namespace App\Modules\Administration;

use App\Models\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class AdminAuditLogger
{
    /** @param array<string, scalar|null> $metadata */
    public function record(User $actor, string $action, string $reason, ?User $target = null, array $metadata = []): void
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500
            || preg_match('/[\x00-\x1F\x7F]|\b[A-Z]{3,16}SESSID\b|\bBearer\s+\S+|\bsk-[A-Za-z0-9_-]{8,}/i', $reason) === 1
        ) {
            throw new InvalidArgumentException('Audit actions require a bounded reason.');
        }
        foreach (array_keys($metadata) as $key) {
            if (preg_match('/(?:authorization|cookie|password|secret|token|api[_-]?key|artifact|pob|item[_-]?text|prompt|response|session|(?:^|_)ip(?:_|$))/i', $key) === 1) {
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
            'correlation_id' => $this->correlationId(),
            'created_at' => now(),
        ]);
    }

    private function correlationId(): string
    {
        $value = Context::get('correlation_id');

        return is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[47][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $value) === 1
            ? $value
            : (string) Str::uuid7();
    }
}
