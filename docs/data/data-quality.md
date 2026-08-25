# Canonical game-data quality

Status date: 2026-08-25. This report describes data that a clean production
installation can resolve from an active, approved ruleset. Test fixtures,
hard-coded presentation catalogs, parser aliases, and candidate repositories
are deliberately excluded.

## Actual active coverage

No approved canonical ruleset is active in a clean installation. Therefore
every requested category has zero active records in both editions. The verified
total population (the denominator needed for a completeness percentage) is
also unavailable, so Lootwright reports the percentage as `unknown` rather
than inventing one.

| Edition | Classes | Ascendancies | Skills | Supports | Passives | Items | Modifiers | Trade vocabulary |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| PoE1 | 0 records; percentage unknown | 0; unknown | 0; unknown | 0; unknown | 0; unknown | 0; unknown | 0; unknown | 0; unknown |
| PoE2 | 0 records; percentage unknown | 0; unknown | 0; unknown | 0; unknown | 0; unknown | 0; unknown | 0; unknown | 0; unknown |

`PostgresDataCoverageReporter` computes this projection from the exact active
ruleset and `canonical_game_data`. An imported ruleset may declare verified
`data_quality.expected_counts`; only then does the UI calculate completeness.
Observed records without a verified denominator are shown as
`unknown_completeness`, not 100%.

## Operationally importable data

`GGG-POE1-SKILLTREE-001` is the only approved canonical producer. An operator
may import the exact reviewed GGG `skilltree-export` commit
`8bd138b32ea2631455cac5935bfab089f826094f`. It can provide PoE1 character
classes, Ascendancies, passive nodes, and keystones. The repository does not
embed that upstream dataset as production data, and the reduced licensed
fixture is not counted above. Activation is explicit and atomic.

No approved producer currently supplies canonical skills, support gems, item
bases, unique items, modifiers, stats, mechanics, jewels, clusters, content
definitions, or Trade vocabulary. No PoE2 canonical producer is approved.
These gaps are visible and block dependent deterministic rules.

## Quality controls

- Canonical identity is edition + immutable ruleset + entity type + external
  identifier. Display names are never identities.
- PoE1 and PoE2 use separate schema IDs and normalizers. Cross-edition input is
  rejected before canonical assembly.
- Each record retains snapshot ID, import time, source/version, and checksums.
- Category authority is deny-by-default. Only the four supported GGG PoE1
  passive-tree categories are seeded as enabled authority.
- Authority precedence is configurable per category. If sources disagree on a
  fact, Lootwright appends a quarantined `canonical_data_conflicts` record and
  selects neither fact; precedence never hides contradiction.
- Update observations are append-only and checksum based. Importing or staging
  a change never activates it automatically.

## Source assessment

| Source/capability | Technical access | License/terms, redistribution, commercial use, cache | Rate/auth | Quality/version/update | Edition and provenance | Classification |
| --- | --- | --- | --- | --- | --- | --- |
| GGG documented developer APIs | Documented OAuth API; no concrete game-data operation enabled | GGG developer terms; operation-specific rights remain to be reviewed | Documented headers; OAuth application required | Official structured; operation-specific versioning | PoE1/PoE2; first-party URLs reviewed | REQUIRES_REVIEW |
| GGG official websites | Public web pages | Terms do not authorize scraping or corpus reuse | Public web limits; no user credentials accepted | First-party but mostly presentation-oriented | Edition/page-specific references | DISABLED for scraping |
| GGG PoE1 `skilltree-export` | Pinned raw GitHub URL or operator file | No repository license file; GGG Terms reference; bounded local snapshot, no assets | No auth; operator-pinned fetch only | Official structured; Git commit and SHA-256 | PoE1; exact commit/checksum | APPROVED_WITH_LIMITS |
| Path of Building Community | Public Git repository | MIT software license; embedded/generated game-data rights not established | No runtime auth; no production fetch | Strong interoperability reference; commit-versioned | PoE1; format and data capabilities separated | APPROVED for format only; game data REQUIRES_REVIEW |
| Path of Building PoE2 | Public Git repository | MIT software license; embedded/generated game-data rights not established | No runtime auth | Beta format reference; commit-versioned | PoE2; exact reviewed commit | EXPERIMENTAL format only; game data REQUIRES_REVIEW |
| RePoE | Public Git repository | Generated rows derive from GGPK/PyPoE; redistribution/commercial authority unresolved | No runtime auth | Structured derived client data; commit-versioned | PoE1 candidate; upstream ownership noted | DISABLED / prohibited production source |
| PoE Wiki Cargo | Public candidate API | CC BY-NC-SA/share-alike and underlying GGG-data implications unresolved for intended production use | Exact limits require review; no auth observed | Community structured; page revisions | PoE1; reviewed URLs only | REQUIRES_REVIEW; adapter disabled |
| poe.ninja economy API | Documented public API | Bounded normalized observations; no raw/icon redistribution; commercial status unresolved | Cache/rate headers; identifiable contact required | Market observations, league + timestamp; never ruleset truth | PoE1 economy source/version/checksum | APPROVED_WITH_LIMITS; default off |
| Path of Exile Trade | Undocumented internal search/fetch/data paths are technically reachable but prohibited | No approved ingestion/redistribution capability | Session secrets never accepted; rate limits never bypassed | Live listings are observations, not canonical facts | Edition-specific vocabulary absent | DISABLED |
| `dat-schema` | Public Git repository | MIT schema; contains no approved game rows | No runtime auth | Schema-only, commit-versioned | Cross-edition schema candidate with no fact authority | APPROVED for schema research only |
| PyPoE/client-derived tooling | Public code but requires client-file extraction for intended data path | Game-data rights unresolved | Local client access prohibited | Derived client data | PoE1 candidate only | DISABLED / prohibited production path |

## Source discovery evidence (2026-08-25)

The following read-only checks were performed against the canonical source
locations. HTTP availability is not permission to import. GitHub API metadata
reported `NOASSERTION` for the Path of Building repositories, RePoE and PyPoE;
the MIT result for `dat-schema` applies to its schema code only. The official
GGG `skilltree-export` repository has no separate licence file, so its source
register entry remains `LicenseRef-GGG-Terms-of-Use` and is limited to the
reviewed commit and checksum. The PoE Wiki Cargo API is technically reachable,
but its CC BY-NC-SA/share-alike and underlying GGG-data implications keep it
disabled. PoEDB and Craft of Exile are recorded as review candidates, not
runtime dependencies. No source is fetched while serving a player request.

| Capability | Technical access | Licence/terms | Cache/redistribution/commercial | Rate/auth | Data quality/versioning | Decision |
| --- | --- | --- | --- | --- | --- | --- |
| GGG documented API | HTTPS documented API; application registration currently unavailable | Operation-specific GGG review required | Unknown until operation approval | OAuth/application and documented limits | First-party, operation-specific versioning | REQUIRES_REVIEW |
| GGG official passive-tree export | Pinned Git/raw file, operator-only | No separate licence file; GGG terms reference | Bounded local snapshot only; no hosted copy/assets | No auth; pinned fetch | Structured, commit + checksum | APPROVED_WITH_LIMITS |
| PoB Community PoE1/PoE2 | Public Git; format parsers implemented independently | MIT software licence; embedded game rows are separate rights question | Format-only local interpretation allowed; no data redistribution | No auth; Git commits | Strong format evidence; PoE2 beta | APPROVED (format only) / REQUIRES_REVIEW (data) |
| RePoE / PyPoE | Public repositories; intended path derives client data | Underlying GGG rights and commercial terms unresolved | Production import/cache/redistribution denied; client inspection prohibited | No runtime auth | Derived/client-versioned, stale or unknown | DISABLED |
| PoE Wiki Cargo | Public HTTP API | CC BY-NC-SA plus underlying GGG-data review | Cache/redistribution/commercial use not approved | Limits require review; no auth observed | Community rows and page revisions | REQUIRES_REVIEW |
| poe.ninja economy | Documented public API | Conditional source-specific review | Normalized, bounded market observations only; no raw/icon retention | Provider cache/rate headers; contact required | League/timestamp observations, not rules | APPROVED_WITH_LIMITS (default off) |
| Official Trade | Website and undocumented internal paths technically reachable | No approved ingestion right; internal paths prohibited | No listing/query/price ingestion | Session secrets prohibited | Live observations, never canonical facts | DISABLED |
| PoEDB / Craft of Exile | Public websites | No reviewed API/data licence or commercial/cache permission | Scraping and hosted extraction disabled | Unknown | Presentation/reference quality only | DISABLED / REQUIRES_REVIEW |

These classifications are mirrored in `source-register.md`, the executable
`policy_data_sources` projection, and adapter status tests. A newly discovered
source must first receive an immutable version, checksum/provenance evidence,
field-level rights review, and a category authority decision before it can
produce canonical data.

Technical accessibility never changes a Policy and Provenance Gate decision.
See the [source register](../compliance/source-register.md) for exact evidence
and [ADR 0025](../adr/0025-canonical-data-authority-and-quality.md) for the
normalization/conflict decision.
