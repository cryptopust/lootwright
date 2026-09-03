<?php

namespace App\Modules\Release;

/** Identifies whether an acceptance run uses canonical production bindings. */
final class RuntimeMarker
{
    public const PRODUCTION_CANONICAL = 'PRODUCTION_CANONICAL';

    public const TEST_FIXTURE = 'TEST_FIXTURE';

    public static function current(): string
    {
        $value = strtoupper(trim((string) config('analysis-workflow.runtime_mode', self::PRODUCTION_CANONICAL)));

        return in_array($value, [self::PRODUCTION_CANONICAL, self::TEST_FIXTURE], true)
            ? $value
            : self::TEST_FIXTURE;
    }

    public static function assertCanonical(): void
    {
        if (self::current() !== self::PRODUCTION_CANONICAL) {
            throw new \RuntimeException('Production acceptance requires PRODUCTION_CANONICAL runtime; TEST_FIXTURE is not allowed.');
        }
    }
}
