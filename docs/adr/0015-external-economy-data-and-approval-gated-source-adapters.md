# ADR 0015: External economy data and approval-gated source adapters

Status: accepted, 2026-08-20.

Lootwright enables only cached, normalized poe.ninja PoE1 economy context behind
an exact endpoint/category allowlist, source switch, policy evidence and
freshness provenance. This is a bounded backend-only integration with a 20-minute
default refresh and a 30-minute schedule, fitting the USD 2025 MVP budget.

poe.ninja builds, profiles, characters, PoB, authentication and page scraping
remain prohibited. Normal official Trade Search remains unavailable because
Lootwright has no documented permission; manual query-free Trade recipes remain
the product boundary. PoE Wiki Cargo, RePoE, remote pobb.in and GGG OAuth remain
disabled until their separate licensing, data-rights, attribution, funding,
registration and scope reviews conclude. Public Stash indexing is deferred for
scope, delay, `service:psapi`, and operational-cost reasons.

Every displayed estimate must carry its source/version/checksum/timestamp and
freshness. It is category-, unique-, gem-, or currency-context only, never an
exact rare-item listing price. AI receives this immutable evidence only.
