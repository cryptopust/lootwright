# Capability-based market intelligence

Lootwright treats market access as a set of independently reviewed
capabilities. A provider can be allowed to read or aggregate observations while
remaining unable to search, generate encoded queries, or create deep links.
Every provider is edition-scoped and must expose the six contract methods:

- `supportsSearch()`
- `supportsListings()`
- `supportsPriceStats()`
- `supportsHistoricalStats()`
- `supportsEncodedSearch()`
- `supportsDeepLinks()`

The PoE1 poe.ninja adapter may read an operator-approved normalized local
economy snapshot; it performs no runtime network request. It remains disabled
until the source switches and Policy/Provenance Gate allow the synchronizer.
The PoE2 provider is fully unavailable until a separate approved PoE2 market
source exists. A future provider may be enabled only by an operator-controlled,
policy-gated synchronizer with documented host, path, authentication, rate
limits, cache TTL, provenance, and attribution.

## Capability matrix

| Capability | PoE1 default | PoE2 default | Notes |
| --- | --- | --- | --- |
| SEARCH | disabled | disabled | No undocumented Trade endpoints or user sessions. |
| READ/LISTINGS | disabled | disabled | No live listing retrieval in request handling. |
| CACHE | model supported | model supported | Only reviewed, bounded observations may be cached. |
| AGGREGATE/ANALYZE | normalized local snapshot | unavailable | Statistics are deterministic and outlier-bounded. |
| STORE/DISPLAY | contextual DTO only | unavailable | Source, timestamp, league, sample size and confidence are mandatory. |
| LINK | disabled by provider | disabled | Generic/manual Trade recipes remain the fallback. |
| GENERATE_FILTERS | manual recipe path | manual path remains inactive | No generated Trade IDs or undocumented query payloads. |

`MarketObservation` contains edition, source/version, league, observation and
expiry timestamps, median and percentile distribution, listing/sample counts,
outlier count, confidence and liquidity. A naked price is not representable.

## Freshness and fallback

`CachedMarketProvider` asks the approved provider first. If a current result is
not available, it may return a cached observation only while its TTL is valid.
Expired observations produce `no_price`; they are never shown as current and
never affect budget eligibility. The planner's deterministic candidate and
finding remain authoritative; market evidence can only enrich ranking and
budget context through `MarketAwareUpgradePlanner`.

All CI tests use fakes. No live Trade, poe.ninja, authentication, purchase,
seller interaction, gameplay automation, or external request is required.
