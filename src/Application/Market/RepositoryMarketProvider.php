<?php

namespace Lootwright\Application\Market;

use Lootwright\Application\Market\Ports\MarketObservationRepository;
use Lootwright\Application\Market\Ports\TradeProvider;
use Lootwright\Domain\Market\MarketObservationBuilder;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\CurrencyCode;

/** Reads only normalized local observations; it never calls an upstream source. */
readonly class RepositoryMarketProvider implements TradeProvider
{
    public function __construct(private GameEdition $providerEdition, private MarketObservationRepository $repository, private TradeProviderCapabilities $capabilities = new TradeProviderCapabilities(priceStats: true)) {}

    public function edition(): GameEdition { return $this->providerEdition; }
    public function capabilities(): TradeProviderCapabilities { return $this->capabilities; }
    public function supportsSearch(): bool { return $this->capabilities->supportsSearch(); }
    public function supportsListings(): bool { return $this->capabilities->supportsListings(); }
    public function supportsPriceStats(): bool { return $this->capabilities->supportsPriceStats(); }
    public function supportsHistoricalStats(): bool { return $this->capabilities->supportsHistoricalStats(); }
    public function supportsEncodedSearch(): bool { return $this->capabilities->supportsEncodedSearch(); }
    public function supportsDeepLinks(): bool { return $this->capabilities->supportsDeepLinks(); }

    public function marketEstimate(TradeSearchRequest $request): MarketEstimate
    {
        if ($request->edition !== $this->providerEdition || ! $this->supportsPriceStats()) {
            return new MarketEstimate(MarketEstimateStatus::NoPrice, reason: 'Provider capability or edition boundary denied this estimate.');
        }
        $rows = $this->repository->prices($request->edition, $request->league, $request->filters);
        if ($rows === []) {
            return new MarketEstimate(MarketEstimateStatus::NoPrice, reason: 'No approved local market observations matched the request.');
        }
        $currency = CurrencyCode::from($rows[0]['currency']);
        if ($currency->isFailure()) {
            return new MarketEstimate(MarketEstimateStatus::NoPrice, reason: 'The normalized market currency is unsupported.');
        }
        $prices = array_map(static fn (array $row): string => $row['price'], $rows);
        $listingCount = array_sum(array_map(static fn (array $row): int => max(0, $row['listing_count']), $rows));
        $first = $rows[0];
        $observation = MarketObservationBuilder::build($request->edition, $first['source'], $first['source_version'], $request->league, $currency->value(), $prices, $listingCount, $first['observed_at'], $first['expires_at']);

        return new MarketEstimate(MarketEstimateStatus::Live, $observation);
    }
}
