<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Validate the Laravel Cloud profile without imposing self-hosted Redis/Horizon
 * requirements. This command is intentionally read-only and fail-closed.
 */
final class CheckCloudConfiguration extends Command
{
    protected $signature = 'deploy:check-cloud-config';

    protected $description = 'Validate the Laravel Cloud production configuration';

    public function handle(): int
    {
        $failures = array_values(array_filter([
            $this->equals('APP_ENV', config('app.env'), 'production'),
            $this->equals('APP_DEBUG', (bool) config('app.debug'), false),
            $this->httpsUrl(),
            $this->secret('APP_KEY', (string) config('app.key'), 32),
            $this->equals('DB_CONNECTION', config('database.default'), 'pgsql'),
            $this->secret('READINESS_TOKEN', (string) config('services.readiness.token'), 32),
            $this->equals('LOG_CHANNEL', config('logging.default'), 'stderr'),
            $this->equals('SESSION_ENCRYPT', (bool) config('session.encrypt'), true),
            $this->equals('SESSION_SECURE_COOKIE', (bool) config('session.secure'), true),
            $this->localArtifactsWhenEnabled(),
            $this->syncQueueWhenAsyncRequired(),
        ], static fn (?string $failure): bool => $failure !== null));

        foreach ($failures as $failure) {
            $this->error($failure);
        }

        if ($failures !== []) {
            return self::FAILURE;
        }

        $this->info('Laravel Cloud configuration satisfies the production baseline.');

        return self::SUCCESS;
    }

    private function httpsUrl(): ?string
    {
        return str_starts_with((string) config('app.url'), 'https://') ? null : 'APP_URL must use HTTPS.';
    }

    private function secret(string $name, string $value, int $minimum): ?string
    {
        $value = trim($value);
        $lower = strtolower($value);

        return strlen($value) >= $minimum && ! str_contains($lower, 'change-me') && ! str_contains($lower, 'placeholder')
            ? null
            : "{$name} must be secret-managed and at least {$minimum} characters.";
    }

    private function localArtifactsWhenEnabled(): ?string
    {
        $enabled = (bool) config('security.emergency.imports') || (bool) config('security.emergency.rulesets');
        $disk = (string) config('filesystems.disks.analysis-artifacts.driver', 'local');

        return $enabled && in_array($disk, ['local', 'file'], true)
            ? 'Durable analysis-artifacts storage is required when imports or rulesets are enabled.'
            : null;
    }

    private function syncQueueWhenAsyncRequired(): ?string
    {
        $asyncRequired = (bool) config('security.emergency.imports')
            || (bool) config('security.emergency.rulesets')
            || (bool) config('ai.enabled');

        if ($asyncRequired && config('queue.default') === 'sync') {
            return 'QUEUE_CONNECTION may not be sync when asynchronous capabilities are enabled.';
        }

        $cacheDriver = (string) config('cache.stores.'.config('cache.default').'.driver', config('cache.default'));

        return $asyncRequired && in_array($cacheDriver, ['file', 'array'], true)
            ? 'A shared Cloud cache is required when asynchronous capabilities are enabled.'
            : null;
    }

    private function equals(string $name, mixed $actual, mixed $expected): ?string
    {
        return $actual === $expected ? null : "{$name} does not match the Laravel Cloud production baseline.";
    }
}
