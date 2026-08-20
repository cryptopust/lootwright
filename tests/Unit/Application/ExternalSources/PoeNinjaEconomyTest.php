<?php

namespace Tests\Unit\Application\ExternalSources;

use App\Modules\ExternalSources\PoeNinja\PoeNinjaEndpoint;
use App\Modules\ExternalSources\PoeNinja\PoeNinjaFailure;
use App\Modules\ExternalSources\PoeNinja\PoeNinjaNormalizer;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Lootwright\Application\ExternalSources\DTO\EconomyCategory;
use Tests\TestCase;

final class PoeNinjaEconomyTest extends TestCase
{
    public function test_endpoint_factory_allows_only_documented_economy_paths_and_categories(): void
    {
        $exchange = PoeNinjaEndpoint::overview('Mirage', EconomyCategory::Currency);
        $stash = PoeNinjaEndpoint::overview('Mirage', EconomyCategory::UniqueWeapon);

        PoeNinjaEndpoint::assertAllowed(PoeNinjaEndpoint::leagues());
        PoeNinjaEndpoint::assertAllowed($exchange);
        PoeNinjaEndpoint::assertAllowed($stash);
        self::assertStringContainsString('/exchange/current/overview?', $exchange);
        self::assertStringContainsString('/stash/current/item/overview?', $stash);
    }

    public function test_unsupported_paths_hosts_and_userinfo_are_denied(): void
    {
        foreach ([
            'http://poe.ninja/poe1/api/economy/leagues',
            'https://example.test/poe1/api/economy/leagues',
            'https://user@poe.ninja/poe1/api/economy/leagues',
            'https://poe.ninja/poe1/api/builds',
            'https://poe.ninja/poe1/api/economy/exchange/current/overview?league=Mirage&type=UniqueWeapon',
        ] as $url) {
            try { PoeNinjaEndpoint::assertAllowed($url); self::fail("{$url} must be denied."); } catch (InvalidArgumentException) { self::addToAssertionCount(1); }
        }
    }

    public function test_normalizer_produces_immutable_normalized_quotes_without_icon_urls(): void
    {
        $quotes = (new PoeNinjaNormalizer)->quotes(json_encode(['lines' => [[
            'currencyTypeName' => 'Divine Orb', 'detailsId' => 'divine-orb', 'chaosValue' => 120.25, 'icon' => 'https://example.test/icon.png',
        ]]], JSON_THROW_ON_ERROR), 'Mirage', EconomyCategory::Currency, CarbonImmutable::parse('2026-08-20T09:30:00Z'), CarbonImmutable::parse('2026-08-20T09:50:00Z'));

        self::assertCount(1, $quotes);
        self::assertSame('Divine Orb', $quotes[0]->evidence->normalizedName);
        self::assertSame('120.250000', $quotes[0]->evidence->normalizedValue);
        self::assertSame('fresh', $quotes[0]->evidence->freshness->value);
        self::assertArrayNotHasKey('icon', $quotes[0]->confidenceMetadata);
    }

    public function test_malformed_or_unexpected_schema_fails_closed(): void
    {
        $normalizer = new PoeNinjaNormalizer;
        foreach (['{', '{}'] as $body) {
            try {
                $normalizer->quotes($body, 'Mirage', EconomyCategory::Currency, CarbonImmutable::now('UTC'), CarbonImmutable::now('UTC')->addMinutes(20));
                self::fail('Unexpected source schema must fail closed.');
            } catch (PoeNinjaFailure) { self::addToAssertionCount(1); }
        }
    }
}
