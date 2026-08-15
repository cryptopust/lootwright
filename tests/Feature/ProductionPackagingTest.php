<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class ProductionPackagingTest extends TestCase
{
    public function test_production_configuration_check_accepts_only_the_default_off_baseline(): void
    {
        config()->set([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://lootwright.org',
            'app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'deployment.lockdown' => true,
            'deployment.release_sha' => str_repeat('a', 40),
            'deployment.trusted_hosts' => ['^lootwright\\.org$'],
            'deployment.trusted_proxies' => ['127.0.0.1'],
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => 'postgres.internal',
            'database.connections.pgsql.username' => 'lootwright_app',
            'database.connections.pgsql.password' => 'fixture-database-password',
            'database.connections.pgsql.sslmode' => 'verify-full',
            'database.connections.pgsql.sslrootcert' => '/run/secrets/postgres-ca.pem',
            'database.redis.default.scheme' => 'tls',
            'database.redis.default.username' => 'lootwright_app',
            'database.redis.default.password' => 'fixture-redis-password',
            'database.redis.default.context.stream.cafile' => '/run/secrets/redis-ca.pem',
            'database.redis.default.context.stream.verify_peer' => true,
            'database.redis.default.context.stream.verify_peer_name' => true,
            'cache.default' => 'redis',
            'queue.default' => 'redis',
            'session.driver' => 'redis',
            'session.secure' => true,
            'session.encrypt' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'services.readiness.token' => 'fixture-readiness-token-32-characters',
            'logging.default' => 'stderr',
            'policy.global_kill_switch' => true,
            'security.emergency.imports' => false,
            'security.emergency.rulesets' => false,
            'security.emergency.external_links' => false,
            'security.emergency.ai' => false,
            'security.emergency.funding' => false,
            'security.outbound.enabled' => false,
            'horizon.dashboard_enabled' => false,
            'funding.requested_enabled' => false,
        ]);

        self::assertSame(0, Artisan::call('deploy:check-config'));
        self::assertStringContainsString('default-off deployment baseline', Artisan::output());

        config()->set('funding.requested_enabled', true);

        self::assertSame(1, Artisan::call('deploy:check-config'));
        self::assertStringContainsString('FUNDING_ENABLED', Artisan::output());
    }

    public function test_production_package_defines_non_root_immutable_separate_roles(): void
    {
        $dockerfile = file_get_contents(base_path('Dockerfile.production')) ?: '';
        $compose = file_get_contents(base_path('deploy/compose.production.yaml')) ?: '';

        self::assertStringContainsString('USER 10001:10001', $dockerfile);
        self::assertStringContainsString('composer install', $dockerfile);
        self::assertStringContainsString('npm ci', $dockerfile);
        self::assertStringNotContainsString('dev:container', $dockerfile);
        self::assertStringContainsString('read_only: true', $compose);
        self::assertStringContainsString('@sha256:${LOOTWRIGHT_IMAGE_DIGEST:?', $compose);
        self::assertMatchesRegularExpression('/\n\s+web:\R/', $compose);
        self::assertMatchesRegularExpression('/\n\s+queue:\R/', $compose);
        self::assertMatchesRegularExpression('/\n\s+scheduler:\R/', $compose);
        self::assertMatchesRegularExpression('/\n\s+migrate:\R/', $compose);
    }

    public function test_production_reference_contains_no_credentials_and_keeps_external_capabilities_off(): void
    {
        $environment = file_get_contents(base_path('deploy/env.production.example')) ?: '';

        foreach (['APP_KEY', 'DB_PASSWORD', 'REDIS_PASSWORD', 'READINESS_TOKEN', 'OPENAI_API_KEY'] as $secret) {
            self::assertMatchesRegularExpression('/^'.preg_quote($secret, '/').'=$/m', $environment);
        }
        foreach (['OPENAI_ENABLED', 'OUTBOUND_NETWORK_ENABLED', 'FUNDING_ENABLED', 'HORIZON_DASHBOARD_ENABLED'] as $disabled) {
            self::assertMatchesRegularExpression('/^'.preg_quote($disabled, '/').'=false$/m', $environment);
        }

        self::assertSame('queue', config('queue.connections.redis.connection'));
        self::assertArrayHasKey('horizon-metadata', config('database.redis'));
    }

    public function test_horizon_dashboard_remains_denied_outside_local_development(): void
    {
        config()->set('horizon.dashboard_enabled', true);
        $this->app->instance('env', 'production');

        $this->get('/horizon')->assertForbidden();
    }
}
