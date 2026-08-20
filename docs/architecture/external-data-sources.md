# External data sources

External data is an application concern. `src/Application/ExternalSources`
contains immutable DTOs and ports only; Laravel HTTP, cache, database and policy
implementations stay in `app/Modules/ExternalSources`.

The only active-capable adapter is `POE-NINJA-ECONOMY-001`, and it remains off
until `POE_NINJA_ENABLED=true`, an operator contact is configured, and the
database Policy and Provenance Gate allows the exact operation. It calls only
the documented PoE1 economy leagues, exchange-overview and stash-item-overview
endpoints. URL, host, scheme and category construction is configuration-owned;
no request accepts a user-controlled URL.

Normalized `PriceEvidence` carries source, version, PoE edition, league,
category, identifier, display name, value/currency, timestamps, freshness, and
payload checksum. It is immutable evidence. AI may explain it but cannot create,
replace or complete it. Icons and raw responses are not persisted.

Snapshots are fresh until `expires_at`, `stale_usable` through the configured
stale window, then expired; absence is unavailable. A temporary failure keeps a
last valid snapshot, but expired values must not be used as current.

PoE Wiki Cargo, RePoE, remote pobb.in and GGG OAuth adapters are disabled,
review-gated candidates. The GGG Currency Exchange interface is limited to
historical hourly aggregates. Public Stash indexing is deferred: it needs
`service:psapi`, has documented delay, and violates the MVP cost and
full-market-indexing boundary.
