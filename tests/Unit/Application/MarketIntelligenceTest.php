<?php

namespace Tests\Unit\Application;

use DateTimeImmutable;
use Lootwright\Application\Market\CachedMarketProvider;
use Lootwright\Application\Market\MarketEstimate;
use Lootwright\Application\Market\MarketEstimateStatus;
use Lootwright\Application\Market\Ports\TradeProvider;
use Lootwright\Application\Market\TradeProviderCapabilities;
use Lootwright\Application\Market\TradeSearchRequest;
use Lootwright\Domain\Market\MarketObservationBuilder;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\CurrencyCode;
use PHPUnit\Framework\TestCase;

final class MarketIntelligenceTest extends TestCase
{
    public function test_statistics_are_contextual_and_trim_extreme_outliers(): void
    {
        $currency = $this->currency('DIVINE');
        $observation = MarketObservationBuilder::build(GameEdition::Poe1, 'FAKE-MARKET', 'v1', 'Settlers', $currency, ['10', '11', '12', '12', '13', '14', '15', '1000'], 42, new DateTimeImmutable('2026-08-25T10:00:00Z'), new DateTimeImmutable('2026-08-25T11:00:00Z'));

        self::assertSame('poe1', $observation->edition->value);
        self::assertSame('Settlers', $observation->league);
        self::assertSame('12', $observation->median->amount);
        self::assertSame(2, $observation->outliersRejected);
        self::assertGreaterThan(0, $observation->confidenceBasisPoints);
        self::assertSame(42, $observation->listingCount);
    }

    public function test_cached_provider_falls_back_only_to_fresh_observations(): void
    {
        $provider = new FakeTradeProvider(MarketEstimateStatus::NoPrice);
        $request = new TradeSearchRequest(GameEdition::Poe1, 'Settlers', ['slot' => 'ring']);
        $observation = MarketObservationBuilder::build(GameEdition::Poe1, 'FAKE-MARKET', 'v1', 'Settlers', $this->currency('DIVINE'), ['5', '6', '7'], 3, new DateTimeImmutable('2026-08-25T10:00:00Z'), new DateTimeImmutable('2026-08-25T11:00:00Z'));
        $cached = new CachedMarketProvider($provider, ['ring' => new MarketEstimate(MarketEstimateStatus::Live, $observation)]);

        self::assertSame(MarketEstimateStatus::Cached, $cached->estimate($request, 'ring', new DateTimeImmutable('2026-08-25T10:30:00Z'))->status);
        self::assertSame(MarketEstimateStatus::NoPrice, $cached->estimate($request, 'ring', new DateTimeImmutable('2026-08-25T12:00:00Z'))->status);
    }

    public function test_provider_capabilities_are_independent_and_no_price_is_explicit(): void
    {
        $provider = new FakeTradeProvider(MarketEstimateStatus::NoPrice);
        self::assertTrue($provider->supportsPriceStats());
        self::assertFalse($provider->supportsSearch());
        self::assertFalse($provider->supportsEncodedSearch());
        self::assertSame(MarketEstimateStatus::NoPrice, $provider->marketEstimate(new TradeSearchRequest(GameEdition::Poe1, 'Settlers', []))->status);
    }

    private function currency(string $value): CurrencyCode
    {
        $result = CurrencyCode::from($value);
        self::assertTrue($result->isSuccess());

        return $result->value();
    }
}

final class FakeTradeProvider implements TradeProvider
{
    public function __construct(private MarketEstimateStatus $status) {}

    public function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }

    public function capabilities(): TradeProviderCapabilities
    {
        return new TradeProviderCapabilities(priceStats: true);
    }

    public function supportsSearch(): bool
    {
        return false;
    }

    public function supportsListings(): bool
    {
        return false;
    }

    public function supportsPriceStats(): bool
    {
        return true;
    }

    public function supportsHistoricalStats(): bool
    {
        return false;
    }

    public function supportsEncodedSearch(): bool
    {
        return false;
    }

    public function supportsDeepLinks(): bool
    {
        return false;
    }

    public function marketEstimate(TradeSearchRequest $request): MarketEstimate
    {
        return new MarketEstimate($this->status, reason: 'fake');
    }
}
