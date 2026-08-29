<?php

namespace Tests\Unit\Application;

use DateTimeImmutable;
use Lootwright\Application\Market\CachedMarketProvider;
use Lootwright\Application\Market\ManualTradeSearchGenerator;
use Lootwright\Application\Market\MarketEstimate;
use Lootwright\Application\Market\MarketEstimateStatus;
use Lootwright\Application\Market\Ports\TradeProvider;
use Lootwright\Application\Market\TradeProviderCapabilities;
use Lootwright\Application\Market\TradeSearchMode;
use Lootwright\Application\Market\TradeSearchRequest;
use Lootwright\Domain\Market\MarketObservationBuilder;
use Lootwright\Domain\Recommendations\UpgradeMarketValueScorer;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Budget;
use Lootwright\Domain\Shared\Value\CurrencyCode;
use Lootwright\Domain\TradePlanning\TradeRecipe;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

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

    public function test_search_generator_exposes_all_manual_modes_without_trade_ids_or_urls(): void
    {
        $rule = new TradeRecipe(
            GameEdition::Poe1,
            new RulesetReference(GameEdition::Poe1, DomainFixtures::ruleset(GameEdition::Poe1)->id->value, '1.0.0', str_repeat('b', 64)),
            'ring', 'Ring', [], null, null, null,
            [['canonical_modifier_id' => 'poe1.modifier.life', 'label' => 'Life', 'minimum' => '80']], [], [], [], [], [],
            'Broad', 'Strict', 'fixture', ['source_id' => 'fixture'], [],
        );
        $generator = new ManualTradeSearchGenerator;
        foreach (TradeSearchMode::cases() as $mode) {
            $plan = $generator->generate($rule, 'Settlers', $mode);
            self::assertSame($mode, $plan->mode);
            self::assertNull($plan->officialTradeUrl);
            self::assertStringNotContainsString('trade_stat_id', $plan->copyText);
        }
    }

    public function test_market_value_score_is_separate_and_bounded(): void
    {
        $budget = Budget::fromDecimal($this->currency('DIVINE'), '10')->value();
        $value = (new UpgradeMarketValueScorer)->score(8_000, $budget, 7_500, 1_000, 9_000);
        self::assertSame(8_000, $value->estimatedBenefitBasisPoints);
        self::assertGreaterThan(0, $value->valueScoreBasisPoints);
        self::assertLessThanOrEqual(10_000, $value->valueScoreBasisPoints);
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
