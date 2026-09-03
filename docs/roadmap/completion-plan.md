# Completion plan for a functional PoE1 + PoE2 public beta

This order follows hard dependencies. A later milestone cannot be declared
complete because its UI or adapter exists while an earlier data or operations
gate is missing.

## Milestone 0 — Evidence baseline (now)

Publish the production-reality audit, keep feature labels honest, capture Cloud
deployment ID/health/migration/mail/backup evidence, and remove stale claims.
No game-data or provider switch is enabled by this milestone.

## Milestone 1 — Cloud operational substrate

Verify PostgreSQL fresh/rollback/reapply on Cloud, configure mail, durable
private object storage, scheduler and either sync-safe or managed queue
semantics, backups/restore, proxy/TLS, `/up`/`/ready`, alerts and rate-limit
state. Produce a signed staging run. This unblocks trustworthy production
workflow testing but not game accuracy.

## Milestone 2 — PoE1 canonical data and ruleset

Resolve permissions and acquire versioned PoE1 skills, supports, item bases,
uniques, modifiers, stats, tags, requirements, mechanics and Trade vocabulary.
Normalize, checksum, conflict-record, validate expected counts, and activate
atomically beside the official passive tree. Expand rules only from canonical
facts; unknown mechanics remain unsupported.

Current gate: **BLOCKED**. No approved producer exists for skills, supports,
items, modifiers, mechanics, or Trade vocabulary. The GGG passive-tree source
is the only limited approved canonical producer and remains operator-triggered
and default-off. Do not promote PoB, RePoE, Wiki, PoEDB, or Craft of Exile rows
without field-level rights and provenance approval.

## Milestone 3 — PoE1 real-player acceptance

Run consented PoB imports across melee/ranged/spell/minion/DoT/defence/content
profiles. Verify goals, budgets, locked equipment, deterministic findings,
dependencies, planner ordering, manual recipes, trace payloads, false positives,
red-team safety and latency. Sign the staging acceptance checklist.

## Milestone 4 — PoE1 market capability (optional for initial beta)

Complete source-specific policy review for poe.ninja or another legitimate
provider. Implement operator-only synchronization, rate limits, cache TTL,
outlier/percentile validation, source attribution and provider health. Connect
market evidence to planner ranking without changing deterministic findings; use
no-price fallback when stale or unavailable. Enable only the reviewed
capabilities (read/cache/aggregate/display); search/deep links remain separate.

## Milestone 5 — PoE1 public beta gate

Promote only after immutable ruleset, real-player staging, security acceptance,
Cloud operations, fixture-free production binding and rollback evidence pass.
Keep AI optional and market capabilities individually switchable.

## Milestone 6 — PoE2 data and engine (independent)

Select an approved PoE2 data source and rights; build a distinct normalizer,
canonical schema, ruleset, mechanics/rule registry, candidate factory and
Trade vocabulary. Add equivalent real-shaped and consented player corpus,
cross-edition leakage tests, mutation/false-positive tests and Cloud staging
acceptance. Do not copy PoE1 identifiers or formulas.

## Milestone 7 — PoE2 beta and dual-edition operations

Enable `poe2` only after its independent release gate passes. Add edition-aware
UI, market provider decisions, observability, rate limits and rollback. PoE2
failure must not change a passing PoE1 status.

## Dependency chain

```text
Cloud evidence -> durable workflow -> approved data -> deterministic rules
-> real-player acceptance -> optional market integration -> PoE1 beta
approved PoE2 data/rules -> PoE2 acceptance -> PoE2 beta
```

Required notice: This product isn't affiliated with or endorsed by Grinding
Gear Games in any way.
