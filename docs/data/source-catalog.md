# Game-data source catalog

Review date: 2026-08-26. Technical access is evaluated separately from rights
to cache, transform, redistribute, and use a source at runtime.

| Source | Edition | Capability decision | License / policy notes | Format / freshness | Recommended use | Implementation |
| --- | --- | --- | --- | --- | --- |
| GGG `skilltree-export` (`GGG-POE1-SKILLTREE-001`) | PoE1 | Read/cache/normalize/store/build-analysis: `APPROVED_WITH_LIMITS` | No separate repository license; `LicenseRef-GGG-Terms-of-Use`; operator-only pinned import, no assets/flavour text | Commit + SHA-256; upstream commits | Classes, ascendancies, passives, keystones | Implemented: `poe:import-passive-tree` |
| GGG documented developer API | PoE1/PoE2 | All data capabilities `REQUIRES_REVIEW` | Operation-specific registration, scopes, retention and redistribution approval missing | Documented schemas only | Future approved operation | Registry only; disabled |
| Path of Building Community | PoE1 | Format interoperability `APPROVED`; game-data `REQUIRES_REVIEW` | MIT software license does not grant rights to embedded/generated GGG rows | XML/share-code, commit pinned | Parse user-submitted PoB envelope | Parser implemented; no canonical import |
| Path of Building Community PoE2 | PoE2 | Format `EXPERIMENTAL`; game-data `REQUIRES_REVIEW` | Same separation of code license and embedded data | Distinct PoB2 XML, early-access commits | Isolated parser experiments | Parser contract; no active ruleset |
| RePoE / PyPoE / client-derived repositories | PoE1 | `UNSUITABLE` for runtime facts | Client extraction and redistribution rights unresolved; client inspection prohibited | Generated/client-versioned | Research only | Disabled |
| PoE Wiki Cargo | PoE1 | `REQUIRES_REVIEW` | CC BY-NC-SA/share-alike and underlying GGG rights require review | Community tables/revisions | Candidate cross-check only | Disabled adapter |
| poe.ninja economy API | PoE1 | Market read/cache/aggregate/display `APPROVED_WITH_LIMITS`, default-off | Documented economy observations, contact/rate controls, never ruleset authority | League/timestamp/TTL | Market context for planner | Operator sync implemented; switches off |
| Official Trade pages/internal endpoints | PoE1/PoE2 | Search/listings/cache/store/filter generation `UNSUITABLE` | Internal endpoints undocumented; sessions and rate-limit bypass prohibited | Live mutable | Generic official homepage link only | Disabled |
| PoEDB / Craft of Exile | PoE1/PoE2 | `REQUIRES_REVIEW` | No reviewed extraction, caching, redistribution or commercial terms | Presentation/reference data | Human research only | Disabled |
| Lootwright-authored rules/fixtures | Both | Original schemas/rule code `APPROVED`; game facts not authoritative | MIT applies only to original material | Versioned code/manifests | Validation and tests | Fixtures test-only |

Capabilities are independent: `CAN_READ`, `CAN_CACHE`, `CAN_NORMALIZE`,
`CAN_STORE`, `CAN_REDISTRIBUTE`, `CAN_USE_FOR_RUNTIME`,
`CAN_USE_FOR_BUILD_ANALYSIS`, `CAN_USE_FOR_MARKET`, and
`CAN_USE_FOR_TRADE_VOCABULARY`. Approval always carries host, operation,
edition, version, checksum, retention, and attribution constraints in the
source register and Policy Gate.

## Current activation

No skills, supports, item bases, uniques, modifiers, stats, mechanics, jewels,
clusters, or Trade vocabulary source is approved for canonical production use.
The only approved canonical producer is the operator-controlled PoE1 passive
tree export, and it remains default-off. PoE2 has no approved canonical data
producer. Unsupported facts must remain explicit.

Required notice: This product isn't affiliated with or endorsed by Grinding
Gear Games in any way.
