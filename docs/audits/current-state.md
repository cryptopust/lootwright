# Current Repository State Audit

Audit date: 2026-08-20. Audited revision:
`69c601266f96152b420d7abbbe0d4944b97abb04` on
`feat/poe1-production-mvp` before the documentation and architecture-test
changes described here.

This audit treats executable code, migrations, bindings, configuration, tests,
and CI as evidence. README statements are not treated as proof. The status
labels mean:

- **IMPLEMENTED**: an end-to-end implementation exists and has direct automated
  coverage, although deployment approval may still be outstanding.
- **PARTIAL**: a useful implementation exists, but a required adapter, data set,
  integration, or production path is incomplete.
- **PLACEHOLDER**: contracts, configuration, or a fail-closed skeleton exists
  without the operational capability.
- **FIXTURE_ONLY**: behavior is demonstrated only with synthetic or reduced
  fixtures and is not production authority.
- **BROKEN**: executable behavior conflicts with a binding repository decision
  or cannot meet its stated contract.
- **MISSING**: no meaningful implementation was found.

## Executive finding

Lootwright is a substantial pre-production Laravel modular monolith, not a
blank scaffold. It has membership and administration, edition-scoped domain
contracts, bounded PoB readers, governed immutable snapshots, an official PoE1
passive-tree importer, a real narrow PoE1 deterministic finding engine,
owner-scoped workflow persistence, and strong policy/security tests.

It is not yet a production-capable dual-game analyzer. PoE2 has a factual
catalog, intake support, a beta structural PoB2 reader, and isolation tests, but
no approved ruleset, passive-tree adapter, or deterministic engine. The PoE1
engine produces a reviewed narrow finding set only after an exact local ruleset
activation; its production path currently emits no upgrade recommendations or
manual Trade recipes. Public PoE2 selection also conflicts with the binding
PoE1-only active-release rule in `AGENTS.md`.

## Capability matrix

| Area | Status | Repository evidence and limitation |
| --- | --- | --- |
| Authentication | **IMPLEMENTED** | Laravel Fortify provides registration, login/logout, reset, verification, password confirmation, profile/password updates, and 2FA. Custom Inertia pages and login/2FA rate limits exist; feature tests cover the principal flows. Production mail delivery still requires operator configuration. |
| Users | **IMPLEMENTED** | `User` has typed role/status casts, active-session enforcement, profile/privacy screens, deletion handling, and owner-scoped analyses/drafts. |
| Admin | **IMPLEMENTED** | Session-authorized admin routes, policies, recent-password checks, mandatory admin 2FA middleware, super-admin protections, user suspension/reactivation/role changes, append-only audit writes, catalog/system screens, and a guarded promotion command exist. The system screen is operationally shallow rather than a full observability product. |
| Game edition selection | **BROKEN** | `GameEdition` and server-side edition validation exist, and the wizard/catalog expose both games. That public PoE2 exposure conflicts with the current `AGENTS.md` active-release rule that preserves PoE2 code but keeps it non-public. Architecture support and release enablement are separate decisions. |
| PoE1 classes | **IMPLEMENTED** | The version-controlled `Poe1CharacterCatalog` exposes seven classes with availability/order/source metadata through the catalog endpoint. It is application code rather than an imported immutable source snapshot. |
| PoE1 ascendancies | **IMPLEMENTED** | Twenty edition-scoped regular Ascendancies are validated against their class. Raider and secondary progressions are not accepted by the current catalog. Source facts remain hardcoded version-controlled metadata rather than a governed catalog snapshot. |
| PoE2 classes | **PARTIAL** | `Poe2CharacterCatalog` contains eight available and four planned classes with availability metadata and tests. It is Early Access factual intake data, not production analysis authority, and is currently exposed contrary to active-release policy. |
| PoE2 ascendancies | **PARTIAL** | Twenty-two regular and one alternate entry are represented; Abyssal Lich requires Witch/Lich. No approved PoE2 ruleset or analysis adapter consumes this catalog. |
| PoB parsing | **PARTIAL** | The PoE1 reader performs bounded local decoding, safe XML parsing, normalization, warnings, unsupported-field disclosure, provenance, edition detection, and policy-gated intake. It intentionally provides structural interoperability rather than full Path of Building format/formula parity. |
| PoB2 parsing | **FIXTURE_ONLY** | A separate beta parser/normalizer handles explicitly supplied PoE2-shaped XML and is tested with a reduced fixture. Production deterministic analysis rejects it because the bound engine is PoE1-only. |
| Passive tree | **PARTIAL** | An operator-only importer accepts a local file or one exact commit-pinned official GGG raw URL, validates/normalizes nodes, quarantines invalid input, stores immutable snapshots, publishes edition-scoped canonical passive/keystone rows, and activates transactionally. The PoE1 engine checks unknown node IDs. There is no full passive calculation model and no PoE2 passive-tree adapter or dataset. |
| Skill gems | **PARTIAL** | PoB normalization captures edition-prefixed gem IDs, level, quality, enabled state, socket/link group, and unsupported fields. There is no governed canonical skill data set or formula implementation. |
| Support gems | **PARTIAL** | Gem groups and links are structurally normalized, but active/support semantics are not backed by a canonical edition-specific gem catalog. |
| Items | **PARTIAL** | Edition-scoped item DTOs exist and PoB item blocks/slot references are bounded and treated as hostile text. There is no production item-text normalizer or approved complete item catalog. Raw item text is not deterministic authority. |
| Modifiers | **PLACEHOLDER** | Edition-scoped modifier identifiers and metadata ports exist. The PoE Wiki adapter is deliberately disabled and no governed production modifier catalog is bound. |
| Rulesets | **PARTIAL** | Immutable source snapshots, quarantine/conflicts, published ruleset versions, edition-scoped canonical data, dataset approval, source links, historical/exact resolution, compatibility statuses, PostgreSQL immutability triggers, and atomic activation exist. Fixture/unavailable data cannot activate. No approved PoE2 ruleset or canonical dataset exists; activation remains an explicit operator workflow. |
| Build normalization | **PARTIAL** | PoB produces canonical, edition-labelled structural facts and persistence hashes. The wizard-plan/item-text path records only a safe envelope and clarification; it does not normalize full game facts. |
| Analysis engine | **PARTIAL** | Production binds `DeterministicAnalysisEngine` to the real `ProductionPoe1DeterministicAnalysisEngine`. It resolves an exact immutable local ruleset and emits deterministic data-quality/resistance/mana/gem/link/slot/passive findings. It fails closed for PoE2 and emits no production recommendations or recipes. |
| Upgrade planner | **FIXTURE_ONLY** | Domain/application ports and deterministic sorting orchestration exist, but production PoE1 execution returns an empty recommendation list. Existing upgrade examples and generator vocabulary are test/demo fixtures. |
| Trade recipes | **PARTIAL** | A query-free, policy-gated PoE1 manual recipe compiler and generic official homepage boundary exist, with undocumented Trade endpoints explicitly denied. Production analysis emits no recipes; PoE2 generation deliberately throws an inactive error. |
| External integrations | **PARTIAL** | The official GGG PoE1 passive-tree operator importer is real and default-off. The public poe.ninja PoE1 economy client/sync path is allowlisted, policy-gated, cached, and default-off. PoE Wiki Cargo and official GGG account/service adapters are disabled skeletons; official Trade Search is hard-disabled. |
| AI | **PARTIAL** | A provider-neutral gateway, strict schemas, exact-code explanation containment, redaction, policy/budget gates, cache, telemetry, and OpenAI Responses transport exist. It is default-off and current seeded policy requires review, so production provider execution is unavailable. Deterministic fallback remains functional. |
| Caching | **IMPLEMENTED** | Laravel cache defaults to the database; database locks are available, AI caching is bounded and owner-keyed, HTTP validators are supported by catalog/economy paths, and cache is never authoritative. There is no need for mandatory Redis. |
| Queues | **IMPLEMENTED** | Database queue is the default. Parse/analysis jobs, after-commit dispatch, transactional outbox recovery, bounded attempts, failure classification, and scheduler commands exist. Redis/Horizon is optional. |
| Rate limits | **IMPLEMENTED** | Named limits cover authentication, anonymous sessions, imports, analysis submission/read, AI, export, deletion, policy reads, and policy administration. |
| Security | **PARTIAL** | CSRF/session boundaries, secure session defaults, active-user middleware, owner authorization, 2FA/recent-password admin gates, strict egress allowlisting, redirect/private-network denial, parser bounds, encrypted retained artifacts, redacted logging, retention/deletion, and security headers have tests. This code audit is not a penetration test, threat-model re-review, or deployment attestation. |
| Testing | **IMPLEMENTED** | Unit, feature, architecture, parser/security, policy, PostgreSQL opt-in, frontend component, and Playwright suites exist. Most default PHP tests use SQLite; PostgreSQL-specific tests require explicit disposable-database variables. |
| CI | **IMPLEMENTED** | GitHub Actions pins actions, runs guardrails, Composer/npm audits, formatting, PHPStan, PHP/frontend/browser tests, docs validation, a PostgreSQL fresh/rollback/reapply cycle, and a production image/config job. A green local run does not prove the latest remote CI run. |
| Production deployment | **PARTIAL** | Production Docker packaging, non-root runtime, process scripts, Laravel Cloud guidance, safe config checks, readiness endpoints, and backup/restore documentation exist. No production deployment, operational smoke evidence, approved public PoE2 release, or complete dual-game ruleset stack was verified in this audit. |

## Concrete architecture problems

1. Public dual-game routes, wizard copy, and README claims disagree with the
   binding PoE1-only active-release scope. The dormant PoE2 architecture should
   remain, but release exposure requires a governing decision change or a
   server-side PoE1 lockdown.
2. The repository contains stale documentation that still says no production
   analyzer exists. A narrow real PoE1 finding engine is now bound, while
   production recommendations and recipes remain absent. Those are different
   states and must not be collapsed into either “complete” or “fixture only.”
3. Character catalogs are version-controlled PHP facts with source metadata,
   not immutable governed source snapshots. This is adequate for deterministic
   validation only if the source review and update process remains explicit.
4. `CanonicalImportedBuild` carries the edition directly, but several nested
   parser products are untyped arrays containing edition-prefixed strings.
   Adapter boundaries enforce isolation today; richer game-specific normalized
   DTOs would reduce the amount of structural runtime validation required.
5. Persistence puts `game_edition` on artifacts, analyses, normalized snapshots,
   builds, outbox rows, rulesets, activations, and source snapshots. Finding and
   recommendation rows inherit edition/ruleset through their owning analysis
   and encrypted payload rather than duplicating foreign keys. Repository write
   transactions enforce this, but direct SQL consumers must join through the
   analysis rather than infer game identity from a code string.
6. `EditionManualTradeRecipeGenerator` contains a PoE2 branch only to fail
   closed. It is an adapter routing boundary, not evidence of PoE2 recipe
   support.
7. The scheduled poe.ninja command is registered even when disabled. The sync
   service must continue to deny before HTTP through both configuration and
   Policy Gate; scheduling alone must never be interpreted as enablement.

## Database findings

The schema uses bigint user identities and UUID workflow/source/ruleset
identities consistently at foreign-key boundaries. The analysis self-reference
is added after the `analyses` table creation so PostgreSQL can observe the UUID
primary key. Source/ruleset lifecycle tables use edition checks, composite
source-version ownership keys, immutable PostgreSQL triggers, and exact-scope
activation uniqueness.

No migration was added by this audit. Existing migration correctness must be
demonstrated with the repository's disposable PostgreSQL suite; SQLite is only
fast feedback.

## Verification record

The final commands and exact results for this audit are recorded in the final
task report. The focused architecture suite passed after adding the explicit
game-edition contracts. No real third-party HTTP request is required by the
test design; HTTP integrations use fakes and default-off egress.

## Readiness verdict

**Not ready as a production-capable PoE1-and-PoE2 analyzer.** The safe baseline
is a shared edition-scoped architecture, a governed PoE1 source/ruleset path,
and a narrow PoE1 finding engine. PoE2 remains structurally isolated but lacks
the independent production data and engine stack required for activation.
