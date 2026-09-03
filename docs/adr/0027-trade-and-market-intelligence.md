# ADR 0027: Trade and market intelligence boundary

Status: accepted, 2026-08-29.

Lootwright exposes market context as optional evidence around deterministic
analysis. The provider-neutral contracts separate search generation, listing
retrieval, price statistics, and historical statistics. Every observation is
edition- and league-scoped and carries source/version, timestamps, TTL,
currency, sample/listing counts, distribution percentiles, outlier handling,
confidence, and liquidity.

The only executable market adapter is a PoE1 poe.ninja adapter reading
operator-approved normalized local snapshots. It never performs a user-request
network call and it cannot provide Trade IDs, query URLs, seller data, or
interaction automation. Official Trade capabilities remain denied by the
source register, so the UI supplies validated copyable Broad, Strict, Budget,
and Alternative recipes.

Market ROI (benefit, cost, availability, dependency cost, and value score) is
typed separately from deterministic finding correctness. Missing or stale
market data yields an explicit no-price state and never blocks analysis.
