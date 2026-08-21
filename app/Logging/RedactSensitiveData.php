<?php

namespace App\Logging;

use Monolog\LogRecord;
use Stringable;
use Throwable;

final class RedactSensitiveData
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->redactString($record->message),
            context: $this->redactValue($record->context),
            extra: $this->redactValue($record->extra),
        );
    }

    private function redactValue(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && preg_match('/(?:authorization|cookie|password|secret|token|api[_-]?key|artifact|pob|prompt|private[_-]?note|session)/i', $key) === 1) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $childKey => $child) {
                $redacted[$childKey] = $this->redactValue($child, is_string($childKey) ? $childKey : null);
            }

            return $redacted;
        }

        if ($value instanceof Throwable) {
            return [
                'exception_type' => $value::class,
                'message' => $this->redactString($value->getMessage()),
            ];
        }

        if (is_string($value) || $value instanceof Stringable) {
            return $this->redactString((string) $value);
        }

        return $value;
    }

    private function redactString(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? '[REDACTED]';

        // Unlabelled exception/context values can still contain an entire raw
        // import or private note. Keep normal operational messages useful, but
        // fail closed instead of retaining a prefix of oversized user input.
        if (mb_strlen($value) > 1024) {
            return '[REDACTED:OVERSIZED]';
        }

        $patterns = [
            '/\bBearer\s+\S+/i',
            '/\bsk-[A-Za-z0-9_-]{8,}\b/',
            '/\b[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\.[0-9a-f]{64}\b/i',
            '/\b(?:api[_-]?key|password|secret|token)=([^\s&]+)/i',
        ];

        $redacted = preg_replace($patterns, '[REDACTED]', $value);

        return is_string($redacted) ? $redacted : '[REDACTED]';
    }
}
