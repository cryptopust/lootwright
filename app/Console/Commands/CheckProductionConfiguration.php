<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class CheckProductionConfiguration extends Command
{
    protected $signature = 'deploy:check-config';

    protected $description = 'Fail closed when production configuration violates the Lootwright deployment baseline';

    public function handle(): int
    {
        $failures = array_values(array_filter([
            $this->equals('APP_ENV', (string) config('app.env'), 'production'),
            $this->equals('APP_DEBUG', (bool) config('app.debug'), false),
            $this->httpsUrl(),
            $this->releaseSha(),
            $this->validateSecret('APP_KEY', (string) config('app.key'), 32),
            $this->equals('DB_CONNECTION', (string) config('database.default'), 'pgsql'),
            $this->present('DB_HOST', config('database.connections.pgsql.host')),
            $this->present('DB_USERNAME', config('database.connections.pgsql.username')),
            $this->validateSecret('DB_PASSWORD', (string) config('database.connections.pgsql.password'), 16),
            $this->equals('DB_SSLMODE', (string) config('database.connections.pgsql.sslmode'), 'verify-full'),
            $this->present('DB_SSLROOTCERT', config('database.connections.pgsql.sslrootcert')),
            $this->equals('REDIS_SCHEME', (string) config('database.redis.default.scheme'), 'tls'),
            $this->present('REDIS_USERNAME', config('database.redis.default.username')),
            $this->validateSecret('REDIS_PASSWORD', (string) config('database.redis.default.password'), 16),
            $this->present('REDIS_TLS_CA_FILE', config('database.redis.default.context.stream.cafile')),
            $this->equals('REDIS_TLS_VERIFY_PEER', (bool) config('database.redis.default.context.stream.verify_peer'), true),
            $this->equals('REDIS_TLS_VERIFY_PEER_NAME', (bool) config('database.redis.default.context.stream.verify_peer_name'), true),
            $this->equals('CACHE_STORE', (string) config('cache.default'), 'redis'),
            $this->equals('QUEUE_CONNECTION', (string) config('queue.default'), 'redis'),
            $this->equals('SESSION_DRIVER', (string) config('session.driver'), 'redis'),
            $this->equals('SESSION_SECURE_COOKIE', (bool) config('session.secure'), true),
            $this->equals('SESSION_ENCRYPT', (bool) config('session.encrypt'), true),
            $this->equals('SESSION_HTTP_ONLY', (bool) config('session.http_only'), true),
            $this->equals('SESSION_SAME_SITE', (string) config('session.same_site'), 'lax'),
            $this->equals('AUTH_REQUIRE_VERIFIED_EMAIL', (bool) config('security.require_verified_email'), true),
            $this->validateSecret('READINESS_TOKEN', (string) config('services.readiness.token'), 32),
            $this->trustedHosts(config('deployment.trusted_hosts')),
            $this->trustedProxies(config('deployment.trusted_proxies')),
            $this->equals('LOG_CHANNEL', (string) config('logging.default'), 'stderr'),
            ...((bool) config('deployment.lockdown') ? [
                $this->equals('POLICY_GLOBAL_KILL_SWITCH', (bool) config('policy.global_kill_switch'), true),
                $this->equals('IMPORTS_ENABLED', (bool) config('security.emergency.imports'), false),
                $this->equals('RULESETS_ENABLED', (bool) config('security.emergency.rulesets'), false),
                $this->equals('EXTERNAL_LINKS_ENABLED', (bool) config('security.emergency.external_links'), false),
            ] : []),
            $this->equals('OPENAI_ENABLED', (bool) config('security.emergency.ai'), false),
            $this->equals('OPENAI_INTENT_ENABLED', (bool) config('source-governance.openai_intent_enabled'), false),
            $this->equals('OPENAI_EXPLANATIONS_ENABLED', (bool) config('source-governance.openai_explanations_enabled'), false),
            $this->equals('POENINJA_ECONOMY_ENABLED', (bool) config('source-governance.poeninja_economy_enabled'), false),
            $this->equals('POEWIKI_IMPORT_ENABLED', (bool) config('source-governance.poewiki_import_enabled'), false),
            $this->equals('GGG_PASSIVE_TREE_IMPORT_ENABLED', (bool) config('source-governance.ggg_passive_tree.enabled'), false),
            $this->equals('OUTBOUND_NETWORK_ENABLED', (bool) config('security.outbound.enabled'), false),
            $this->equals('HORIZON_DASHBOARD_ENABLED', (bool) config('horizon.dashboard_enabled'), false),
            $this->equals('FUNDING_ENABLED', (bool) config('funding.requested_enabled'), false),
            $this->equals('FUNDING_ACCEPTING_FUNDS', (bool) config('security.emergency.funding'), false),
        ], static fn (?string $failure): bool => $failure !== null));

        if ($failures !== []) {
            foreach ($failures as $failure) {
                $this->error($failure);
            }

            return self::FAILURE;
        }

        $this->info('Production configuration satisfies the default-off deployment baseline.');

        return self::SUCCESS;
    }

    private function httpsUrl(): ?string
    {
        $url = (string) config('app.url');

        return str_starts_with($url, 'https://') ? null : 'APP_URL must use HTTPS.';
    }

    private function releaseSha(): ?string
    {
        $sha = config('deployment.release_sha');

        return is_string($sha) && preg_match('/^[a-f0-9]{40}(?:[a-f0-9]{24})?$/D', $sha) === 1
            ? null
            : 'APP_RELEASE_SHA must be a lowercase 40- or 64-character commit digest.';
    }

    private function validateSecret(string $name, string $value, int $minimumLength): ?string
    {
        $value = trim($value);
        $lower = strtolower($value);

        return strlen($value) >= $minimumLength
            && ! str_contains($lower, 'change-me')
            && ! str_contains($lower, 'placeholder')
            ? null
            : "{$name} must be secret-managed and at least {$minimumLength} characters.";
    }

    private function present(string $name, mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? null : "{$name} must be configured.";
    }

    private function trustedHosts(mixed $value): ?string
    {
        if (! is_array($value) || $value === []) {
            return 'TRUSTED_HOSTS must contain explicit entries.';
        }
        foreach ($value as $host) {
            if (! is_string($host) || ! str_starts_with($host, '^') || ! str_ends_with($host, '$') || str_contains($host, '*')) {
                return 'TRUSTED_HOSTS entries must be anchored exact regexes without wildcards.';
            }
        }

        return null;
    }

    private function trustedProxies(mixed $value): ?string
    {
        if (! is_array($value) || $value === []) {
            return 'TRUSTED_PROXIES must contain explicit entries.';
        }
        foreach ($value as $proxy) {
            if (! is_string($proxy) || in_array($proxy, ['*', '**', '0.0.0.0/0', '::/0'], true)) {
                return 'TRUSTED_PROXIES may not trust every address.';
            }
        }

        return null;
    }

    private function equals(string $name, mixed $actual, mixed $expected): ?string
    {
        return $actual === $expected ? null : "{$name} does not match the production baseline.";
    }
}
