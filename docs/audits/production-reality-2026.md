# Production reality audit — 2026-08-26

Status: evidence reconciliation, not a release approval. This audit uses the
current checkout, executable bindings, migrations, routes, configuration, CI,
tests, and the operator-provided fact that the application is deployed on
Laravel Cloud. No Cloud dashboard or production database was available to this
workspace, so runtime health and real traffic are **UNVERIFIED**.

## Executive conclusion

Lootwright is a Laravel 13 modular monolith with a real authenticated workflow,
bounded PoB importers, governed source/ruleset lifecycle, a narrow PoE1
deterministic analyzer, deterministic upgrade/recipe contracts, optional AI,
and a capability-based market subsystem. It is not yet a functional public
PoE1 + PoE2 beta: canonical game-data coverage and approved rulesets are the
critical dependency, and the Cloud deployment has not been independently
smoke-tested from this checkout.

The public edition allowlist is still `poe1` (`config/game-editions.php`). PoE2
code is intentionally retained but the public route and production binding do
not provide PoE2 analysis. This is safe isolation, not PoE2 beta support.

## Classification legend

- **WORKING_PRODUCTION** — executable production binding exists; operational
  deployment evidence may still be outstanding.
- **WORKING_BUT_INCOMPLETE** — useful path works but lacks required coverage or
  operational proof.
- **EXPERIMENTAL** — limited, pre-release, or gated implementation.
- **FIXTURE_ONLY** — demonstrated only by project-created fixtures.
- **FAKE_PROVIDER_ONLY** — tests/fakes exist; no live provider is enabled.
- **DISABLED_BY_POLICY** — explicit configuration or policy gate denies use.
- **BROKEN** — code and the current intended contract disagree.
- **MISSING** — no implementation/evidence found.
- **OBSOLETE_DOCUMENTATION** — historical claim conflicts with current code.

## Feature inventory

| Area | Status | Evidence and reality |
| --- | --- | --- |
| Laravel Cloud deployment | WORKING_BUT_INCOMPLETE | `docs/deployment/laravel-cloud.md`, production Docker/Cloud configuration, `/up` and `/ready` exist. User reports Cloud deployment; this checkout has no dashboard URL, deployment ID, logs, or smoke artifact, so health, TLS, mail, backups, and environment values are unverified. |
| Database | WORKING_BUT_INCOMPLETE | PostgreSQL migrations/repositories and CI job exist. Local default tests use SQLite; no reachable PostgreSQL instance was available for this audit. |
| Queues | WORKING_BUT_INCOMPLETE | Database/sync queue paths, outbox and jobs are implemented. Cloud guide deliberately uses sync and does not prove a managed worker is active. Horizon is optional/self-hosted. |
| Scheduler | WORKING_BUT_INCOMPLETE | `routes/console.php` schedules pruning, outbox and source sync. Cloud scheduler activation and observed heartbeats are unverified. |
| Cache | WORKING_PRODUCTION | Laravel database/array/cache abstractions and bounded AI/economy cache paths exist; Redis/Valkey is optional. |
| Storage | WORKING_BUT_INCOMPLETE | Encrypted local artifact storage works in tests, but Cloud filesystems are ephemeral. Durable private object storage is not configured, so queued artifact workflows remain blocked. |
| Authentication/user management | WORKING_PRODUCTION | Fortify auth, verification, sessions, ownership, deletion, 2FA and recent-password gates are implemented and feature-tested. Production mail delivery is unverified. |
| Admin panel/RBAC/audit | WORKING_PRODUCTION | Admin routes, roles, policy evidence, kill switches, source imports and release dashboard are implemented behind authorization. |
| PoE1 importer | WORKING_BUT_INCOMPLETE | Bounded user-submitted PoB1 XML/share-code parsing and normalization are real. Full PoB calculation parity and staging real-player acceptance are missing. |
| PoE2 importer | EXPERIMENTAL | Edition-isolated PoB2-shaped parser/normalizer and hostile-input tests exist. It is structural interoperability, not a supported analysis product. |
| PoE1 game data | WORKING_BUT_INCOMPLETE | Official passive-tree import path is policy-gated and operator-only. Classes/catalog facts exist; no complete approved skills, supports, items, modifiers or mechanics dataset is active. |
| PoE2 game data | MISSING | No approved canonical PoE2 producer/ruleset is active. |
| Rulesets | WORKING_BUT_INCOMPLETE | Immutable versioned ruleset lifecycle, checksums, approval, atomic activation and conflict recording exist. Clean installations have no active approved dataset by default. |
| Passives/keystones | WORKING_BUT_INCOMPLETE | PoE1 passive-tree import and membership checks are supported after activation; PoE2 unavailable. |
| Skills/supports/items/modifiers | MISSING for production authority | Parser fields and DTOs exist; canonical production vocabulary and mechanics coverage do not. Fixture vocabulary is not production data. |
| Classes/ascendancies | WORKING_BUT_INCOMPLETE | Edition-scoped catalog and validation exist, but completeness is not proven against an active immutable dataset. |
| Analysis engine | WORKING_BUT_INCOMPLETE | PoE1 production binding runs a narrow deterministic finding set and fails closed without an approved exact ruleset. PoE2 production analyzer is absent. |
| Upgrade planner | WORKING_BUT_INCOMPLETE | Deterministic graph, constraints, dependencies, budgets and market-aware enrichment contracts exist; breadth depends on findings and approved candidate vocabulary. |
| Trade engine | EXPERIMENTAL | Manual recipe compiler/renderers and unsupported-filter refusals are tested. Official Trade search/deep-link generation is not enabled; recipes are not evidence of live listings. |
| Market data | FAKE_PROVIDER_ONLY | Capability contract, contextual observations, statistics, TTL fallback and fake-provider tests exist. PoE1 poe.ninja sync is default-off and not connected to the Cloud production planner; PoE2 unavailable. |
| AI | EXPERIMENTAL | Provider-neutral gateway, schema validation, quotas, circuit breaker and deterministic fallback exist. OpenAI egress is default-off; no production opt-in/latency/cost evidence is present. |
| Source adapters | WORKING_BUT_INCOMPLETE | GGG passive-tree and poe.ninja adapters have staging/policy gates; disabled GGG API, PoE Wiki, PoE2 and Trade adapters remain non-executable. |
| PoE Wiki | DISABLED_BY_POLICY | Adapter boundary and source record exist; terms, redistribution and commercial review are unresolved. |
| poe.ninja | DISABLED_BY_POLICY | Documented economy sync exists, but switches and policy gate default deny. No live price should be shown. |
| GGG integration | WORKING_BUT_INCOMPLETE | Official passive-tree export import is bounded and operator-controlled. No undocumented Trade paths, client access or credential use exists. Other GGG APIs require operation-specific review. |
| `pobb.in`/remote build URLs | DISABLED_BY_POLICY | Local pasted wrapper can be parsed; arbitrary remote fetch is not implemented. |
| Build URLs/external sources | EXPERIMENTAL | URL envelope/format handling exists without remote retrieval. External build discovery/scraping is absent. |
| Security | WORKING_BUT_INCOMPLETE | Parser bounds, SSRF/egress guards, CSRF, authorization, rate limits, redaction and retention tests pass; Cloud penetration, proxy/TLS and incident evidence are unverified. |
| Rate limits | WORKING_PRODUCTION | Named authentication, import, analysis, AI, export, deletion, policy and admin throttles are configured and tested. Provider limits still require source-specific operations evidence. |
| Observability | WORKING_BUT_INCOMPLETE | Structured logs, audit rows, release dashboard metrics and AI/source telemetry exist. No Cloud alert routing, SLOs or live dashboards were supplied. |
| Health checks | WORKING_BUT_INCOMPLETE | Dependency-free `/up` and token-protected `/ready` exist. Cloud probe configuration and production responses are unverified. |
| Frontend | WORKING_BUT_INCOMPLETE | Inertia/Vue pages and component tests/build pass. Demo/fixture pages remain distinct from owner-scoped production result UX. |
| Mobile/responsive UX | EXPERIMENTAL | Responsive CSS/components and browser snapshots exist; no device-matrix staging acceptance was available. |
| Localization | WORKING_BUT_INCOMPLETE | English/Turkish locale plumbing exists; coverage and encoding consistency need product review. |

## Fixture and fake-provider dependencies

Project-created PoB1/PoB2 fixtures, golden snapshots, synthetic trade cases,
fake AI, and fake market providers prove deterministic contracts only. They do
not prove game-data accuracy, live prices, Cloud health, real-player UX, or
provider permission. Production must read validated local snapshots and active
rulesets, never test fixtures.

## Obsolete or misleading documentation

- `docs/audits/current-state.md` is dated 2026-08-20 and audits commit
  `69c6012`; it predates the production planner, market subsystem, and current
  test counts. Treat it as historical.
- `docs/release/mvp-readiness.md` and portions of `docs/progress.md` repeat
  earlier “no production analyzer” statements. The current binding is a narrow
  PoE1 production analyzer, still incomplete and fail-closed.
- `docs/deployment/laravel-cloud.md` correctly describes a staging procedure,
  but its “does not connect/deploy” language is not evidence about the already
  deployed application. A deployment health artifact is still required.
- Older ADRs that say “no market information” or “PoE2 inactive” are policy
  decisions for the former release scope, not capability-model limits. Current
  code now permits reviewed market capability contracts, while PoE2 remains
  unavailable because its data/ruleset dependency is missing.

## Production blockers to a PoE1 + PoE2 public beta

1. Approve and activate complete, edition-specific canonical datasets and
   rulesets (PoE1 skills/supports/items/modifiers/mechanics; PoE2 all required
   categories), with checksums, conflict decisions and update validation.
2. Expand deterministic rules and candidate factories against those datasets,
   then run real-player staging acceptance for import, findings, planner,
   constraints, recipes and traceability.
3. Connect only an approved market provider to production through a scheduled,
   rate-limited synchronizer; expose contextual prices with TTL, league,
   sample and confidence, and retain no raw prohibited payloads.
4. Configure Cloud PostgreSQL, mail, durable private artifacts, scheduler/
   worker choice, backups/restore, proxy/TLS, alerts and readiness probes;
   capture signed operational evidence.
5. Build and sign independent PoE2 release evidence before adding `poe2` to the
   public allowlist. PoE2 architecture alone is not beta support.

## Current independent statuses

- **PoE1: WORKING_BUT_INCOMPLETE / not public-beta ready.** Safe import and a
  narrow deterministic path exist, but data breadth, market/planner integration
  and Cloud/staging evidence are incomplete.
- **PoE2: EXPERIMENTAL / unavailable.** Structural parser/domain code exists;
  no production dataset, analyzer, planner, or public route is enabled.

## Verification run for this audit

Passed in this workspace on 2026-08-26:

- `composer validate --strict`, `composer audit`
- `composer run format:check`, `composer run analyse`, `composer run ci:guardrails`
- `composer run test` / `php artisan test`: 1009 tests, 1007 passed, 2 skipped,
  14663 assertions
- acceptance (6/6), architecture (584/584), parser/security (45/45), and
  policy-gate (112/112) suites
- `composer run eval:fast`: 31/31 cases passed
- `npm ci`, `npm audit --audit-level=high`, lint, typecheck, Vitest (23/23),
  and Vite production build
- `scripts/validate-docs.ps1` and `git diff --check`

Not fully passing or unavailable:

- Playwright E2E: 7/8 passed; `trade-desktop` failed its existing screenshot
  comparison (`trade-1440` expected 2281px high, received 2057px). The golden
  image was not rewritten.
- PostgreSQL migration/rollback, Laravel Cloud dashboard/runtime smoke,
  backup-restore, mail delivery, and signed staging player acceptance could
  not be verified from this workspace.
- The in-app browser connector was unavailable, so no independent deployed-URL
  inspection was possible.

Required notice: This product isn't affiliated with or endorsed by Grinding
Gear Games in any way.
