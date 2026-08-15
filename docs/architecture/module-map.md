# Module Map

Lootwright uses a Laravel 13 modular monolith with a pure-PHP deterministic core.

```mermaid
flowchart TB
    UI[Inertia 3 + Vue 3 UI]
    HTTP[Laravel HTTP / CLI / Horizon]
    APP[Application use cases and ports]
    GATE[Policy and Provenance]
    INGEST[Build Ingestion]
    ANALYSIS[Deterministic Analysis]
    RECO[Recommendations]
    TRADE[Manual Trade Planning]
    RULES[Ruleset Catalog]
    P1[PoE1 adapter]
    P2[PoE2 adapter - inactive]
    INFRA[PostgreSQL / Redis / AI adapters]

    UI --> HTTP --> APP
    APP --> GATE
    APP --> INGEST
    APP --> ANALYSIS --> RECO --> TRADE
    INGEST --> P1
    INGEST -. phase two .-> P2
    ANALYSIS --> RULES
    P1 --> RULES
    P2 -. isolated .-> RULES
    APP --> INFRA
    GATE --> INFRA
```

Arrows mean permitted dependency or invocation. Domain packages do not point to Laravel infrastructure.

## Planned code ownership

| Location | Responsibility | May depend on | Must not depend on |
| --- | --- | --- | --- |
| `src/Domain/Shared` | Game identity, evidence, provenance references, shared value objects and ports | PHP standard library | Laravel, adapters, storage, AI |
| `src/Domain/BuildIntake` | Player intent, parser snapshots, canonical-build boundary, normalization port | Shared, PoE Catalog, Rulesets | Laravel, persistence, provider SDKs |
| `src/Domain/PoeCatalog` | Opaque game-scoped catalog identifiers and canonical item selections | Shared | real datasets without approved provenance, adapters, Laravel |
| `src/Domain/Rulesets` | Immutable ruleset identity and exact-resolution port | Shared, Policy and Provenance | mutable publication state, Laravel |
| `src/Domain/Analysis` | Deterministic metrics and findings | Shared, Build Intake, Rulesets | HTTP, Eloquent, queues, AI |
| `src/Domain/Recommendations` | Deterministic ranking and explanations-as-data | Shared, Build Intake, Analysis | provider prose, market state |
| `src/Domain/TradePlanning` | Abstract manual filter recipe | Shared, Recommendations | Trade IDs, URLs, browser/API clients |
| `src/Domain/PolicyProvenance` | Sources/versions, permission evidence, exact capability rules, decisions, effective periods, kill switches, and pure evaluation | Shared | external clients, database types, feature-flag overrides |
| `src/Domain/UsageFunding` | Usage port and disabled funding policy | Shared, Policy and Provenance | payment providers, funding entitlements |
| `src/GameAdapters/PoE1` | PoE1 parsing and rule interpretation | shared ports, PoE1 ruleset contracts | PoE2 code, Laravel |
| `src/GameAdapters/PoE2` | PoE2 parsing and rule interpretation | shared ports, PoE2 ruleset contracts | PoE1 code, Laravel |
| `src/Application` | Use cases, commands, queries, workflow states, DTOs, and provider-neutral ports | all domain packages through public APIs | concrete Laravel/AI SDK, database, queue, HTTP, or filesystem types |
| `app/Modules/PolicyProvenance` | Seeded source register, policy persistence, exact capability decisions, audit, evidence administration, and kill-switch adapter | Policy and Provenance port, Laravel | domain formulas, raw user content, provider secrets |
| `app/Modules/Rulesets` | Import, checksum, review, activation, repository adapter | Application ports, Laravel | mutating published rulesets |
| `app/Modules/BuildIntake` | Policy-gated PoB intake orchestration, owner-scoped encrypted persistence, idempotency, deletion, and expiry pruning | Build Intake domain port, adapter coordinator, Policy and Provenance port, Laravel | game formulas, raw-input persistence, external fetching |
| `app/Modules/Analysis` | PostgreSQL workflow repository, encrypted raw-artifact handoff, exact-resolution policy adapter, Horizon jobs, lifecycle events, and deletion coordination | Application workflow ports, domain ports, Laravel | game formulas, mutable analysis snapshots, provider authority |
| `app/Modules/Identity` | Expiring anonymous privacy-session credentials, secret generation, and HTTP-principal resolution | Application identity ports, Laravel | GGG credentials, IP/device identity storage, domain rules |
| `app/Modules/TradePlanning` | Edition routing and exact Policy Gate authorization for local manual-recipe generation and the single generic homepage link | Application Trade Planning ports, isolated game adapters, Policy and Provenance port, Laravel | Trade HTTP clients, listings, prices, encoded links, browser automation |
| `app/Modules/AI` | OpenAI Responses HTTP adapter, exact Policy Gate adapter, PostgreSQL budget/audit adapters, privacy-gated cache, redaction, and context hashing | AI application ports, Laravel | authoritative facts or scoring, raw prompt logging, provider types in `src/` |
| `app/Modules/Funding` | Fail-closed funding-status adapter and configuration-only operating-cost projections | Funding application port, Policy and Provenance port, Laravel configuration | payments, donor identity, player/build data, entitlements, recommendation influence |
| `app/Security` and `app/Http/Middleware` | Security headers, HMAC rate keys, egress allowlist/DNS guard, verification and emergency boundaries | Laravel delivery and infrastructure | game rules, provider authority, raw-content telemetry |
| `resources/js` | Inertia pages, localized Vue components, accessible fixture states, and explicit manual user interaction | typed page contracts | authoritative analysis, provider calls, Trade automation |

## Interaction rules

- Public module APIs are typed PHP interfaces and immutable DTOs. Internal classes stay internal.
- A module owns its tables and Eloquent models. Other modules use its application port, not direct queries.
- Synchronous calls are preferred inside the process. Queue only bounded, idempotent work that benefits from retry or latency isolation.
- Laravel events may notify in-process secondary behavior, but event logs are not the source of truth and event sourcing is prohibited.
- Database transactions end at a use-case boundary. Cross-module transactions must be explicit and tested.
- The narrow workflow outbox is limited to parse/analysis dispatch. It is a
  recovery mechanism for the PostgreSQL-to-Redis commit boundary, not a domain
  event store; publisher rows are locked and retries are bounded.
- Cross-module deletion uses the application-owned `SupplementalUserDataEraser`
  port; the Analysis repository never reads Build Intake tables directly.
- Domain results carry evidence and version identities; presentation may format but not recalculate them.
- New modules require a concrete bounded responsibility and an update to this map.

The detailed namespace and automated dependency matrix are documented in the
[domain foundation](domain-foundation.md).

The route-level presentation contract and its fixture/production boundary are
documented in [interface workflows](../product/interface-workflows.md).

## Ruleset catalog

Every published ruleset record contains at least:

```text
game
patch
league (nullable only when genuinely league-independent)
source_id
source_version
parser_version
checksum_sha256
published_at
effective_at
provenance_record_id
```

Published content is immutable. Corrections create a new version and supersession link. Activation fails closed if the checksum, parser compatibility, game, or provenance does not match.
