# PoE1 Production MVP Execution Plan

Status: approved execution plan; no production analyzer is active.

Last repository verification: 2026-08-20, branch
`feat/poe1-production-mvp`, baseline commit `688df4b`.

This plan is based on code, configuration, migrations, routes, bindings, and
tests. README claims are not treated as implementation evidence. The active
release scope is Path of Exile 1 only. Existing PoE2 code remains in the
repository for isolation and future adapter work, but public PoE2 behavior is a
release blocker until it is disabled.

## Verified technical baseline

| Area | Repository evidence | Verified state |
| --- | --- | --- |
| Runtime | `composer.json`, `composer.lock`, `package.json`, `compose.yaml` | Laravel 13.25.0, PHP `^8.4` (local 8.4.24), Inertia 3, Vue 3, TypeScript, PostgreSQL 18 development image; PHPUnit 12 and Vitest 4 |
| Architecture | `src/`, `app/Modules/`, `tests/Architecture/DomainBoundaryTest.php` | Framework-independent domain/application code and Laravel infrastructure are separated; guardrails scan dependency directions |
| Policy | `src/Domain/PolicyProvenance/`, `app/Modules/PolicyProvenance/`, policy migrations and tests | Deny-by-default decisions, evidence records, audits, and global/source/capability kill switches exist |
| Identity | Fortify provider/actions, auth routes, `User`, middleware, policies, membership migration | Registration, login, verification, password recovery, member ownership, admin/super-admin RBAC, suspension, 2FA controls, and append-only admin audit exist |
| PoE1 intake | `Pob1Parser`, `Pob1Normalizer`, shared hardened envelope/XML parser, build-intake controllers | Explicitly pasted PoB1 input can be decoded and normalized in a bounded format-only workflow; no URL is fetched and no production game formula is implied |
| Workflow | `src/Application/Workflow`, PostgreSQL repository, encrypted artifact storage, jobs and outbox | Idempotent submission, parsing, lifecycle persistence, deletion/export, provenance projections, and queue orchestration exist |
| Analyzer | `AppServiceProvider`, `UnavailableDeterministicAnalysisEngine` | The production port is deliberately bound to an unavailable engine. It always fails closed because no approved immutable ruleset/analyzer exists |
| Findings UI | demo workspace and ARPG components | Findings, evidence, upgrades, and recipes are fixture-backed presentation, not production results |
| Recommendations | domain ports and `CreatePrioritizedUpgrades` | Provider-neutral orchestration and sorting contracts exist; no production PoE1 analyzer/planner implementation is bound |
| Manual Trade | Trade Planning DTOs/use cases, PoE1 generator, disabled official search provider | A policy-gated, query-free manual recipe compiler exists with fixture vocabulary. No live listing/search integration exists |
| External sources | `config/external-sources.php`, poe.ninja adapter, disabled Wiki/GGG/Trade adapters | poe.ninja implementation exists but defaults off; PoE Wiki Cargo and GGG adapters are disabled. These are not analyzer prerequisites |
| Optional AI | `config/ai.php`, provider-neutral gateway and OpenAI adapter | Adapter and budgets exist, but AI defaults off and Policy Gate review is required. AI has no deterministic authority |
| Storage | eleven migrations, PostgreSQL repository, `phpunit.xml` | Production targets PostgreSQL; ordinary tests currently use in-memory SQLite. Two disposable-PostgreSQL integration tests are opt-in |

## Confirmed scope drift and blockers

The following facts must be addressed before a PoE1 production release:

1. `GET /api/catalog/{game}/character-options` currently accepts `poe1|poe2`.
2. The analysis wizard publicly offers PoE2, and the container registers the
   PoE2 parser/normalizer. These paths must fail closed or become non-public
   without deleting the dormant implementation or isolation tests.
3. The governed source/snapshot/ruleset lifecycle now exists (ADR 0017), but
   `POE1-RULES-001` still has no complete approved production fact source.
   Content-addressed import, quarantine, immutable publication, supersession,
   atomic activation and exact resolution are implemented; a source-specific
   approved schema/fixture and production ruleset remain later gates.
4. The deterministic engine binding is intentionally unavailable. Therefore
   no user submission can yield production findings, recommendations, or
   recipes.
5. PoE1 item-text import is not implemented as an analysis-grade parser.
6. Findings and upgrade pages are fixture-backed. Manual recipe vocabulary is
   fixture-only and cannot be promoted by UI copy.
7. Disposable PostgreSQL 18.4 fresh migration, constraint introspection,
   rollback/reapply and lifecycle behavior passed on this workstation on
   2026-08-20. Production backup/restore rehearsal remains a later operational
   gate; SQLite continues to be fast feedback rather than PostgreSQL proof.
8. Source and permission reviews expire. No dataset or adapter may silently
   become production authority because its code exists.
9. The scheduled poe.ninja command is registered, but source configuration is
   off by default. User requests must never trigger it, and production analysis
   must work without it.

## Provenance and source boundary

Each normalized fact must carry one origin and remain traceable through every
finding and recommendation:

| Origin | Required identity | Production use |
| --- | --- | --- |
| User input | owner/session scope, submission time, input digest, parser version, explicit consent | Private analysis input only; never redistributed |
| Official GGG export | exact documented export family, game/patch/version, retrieval or user-export evidence, checksum, permission record | Only after an exact reviewed source record; no undocumented Trade resource |
| PoE Wiki | exact page/API source, retrieved version/time, factual-field allowlist, license/attribution and redistribution decision | Disabled by default; no runtime user-request fetch; production use waits for legal/policy approval |
| poe.ninja | `POENINJA-ECONOMY-001`, endpoint family, source version, league/category, fetched/expiry time, checksum | Optional cached market context only; disabled by default and never deterministic rule authority |
| Lootwright-original rule | rule ID, ruleset version, source references, reviewer, checksum | May express reviewed deterministic logic without inventing game facts |

RePoE, PyPoE, `dat-schema`, PoEDB, Craft of Exile, scraped pages, and
undocumented APIs are excluded as production sources. Reconsideration requires
new primary evidence, source-register changes, an accepted ADR, and explicit
authorization; it is not part of Prompts 1-9.

## Dependency order

```text
Prompt 1: PoE1 public-scope lockdown
    -> Prompt 2: source/provenance decision
        -> Prompt 3: immutable ruleset catalog
            -> Prompt 4: production PoE1 normalization
                -> Prompt 5: deterministic vertical slice
                    -> Prompt 6: recommendation + recipe products
                        -> Prompt 7: production result experience
                            -> Prompt 8: operational hardening
                                -> Prompt 9: release candidate assurance
```

Prompts may be split into smaller changes, but a later prompt cannot bypass an
earlier exit gate. Historical Prompt numbers in `docs/progress.md` remain an
audit trail; the mapping below is the next PoE1 production execution sequence.

## Prompt 1 — Lock public scope to PoE1

Deliverables:

- Remove PoE2 choices and claims from public routes, wizard submissions,
  imports, caches, scheduled work, API responses, and public documentation.
- Preserve PoE2 source, migrations, fixtures, and cross-game rejection tests.
- Add server-side denials proving a forged PoE2 request cannot enter the public
  workflow; frontend hiding is insufficient.

Exit gate:

- Route, request, feature, browser, and negative API tests prove that only PoE1
  is public. Architecture/isolation tests still prove the dormant code cannot
  leak PoE1 identifiers.

## Prompt 2 — Approve the PoE1 source and provenance model

Deliverables:

- Select the narrow factual inputs needed by the first analyzer slice.
- Record exact URLs/exports, versions, retrieval dates, checksums, allowed
  fields, transformation/redistribution limits, attribution, review expiry,
  funding implications, and kill-switch scope.
- Keep PoE Wiki and poe.ninja off by default. Do not choose RePoE, PyPoE,
  `dat-schema`, PoEDB, Craft of Exile, scraped content, or undocumented APIs.

Exit gate:

- Source register, capability matrix, ADR, fixtures, and Policy Gate denial
  tests agree. Unknown, expired, or unapproved facts cannot enter staging.

## Prompt 3 — Implement the immutable PoE1 ruleset catalog

Dependencies: Prompt 2.

Deliverables:

- Implement local, idempotent import staging; schema validation; checksum and
  parser compatibility verification; review; activation; supersession; and
  exact PoE1 patch/league resolution.
- Published rulesets are immutable and identified by game, patch, league where
  relevant, source version, parser version, and SHA-256 checksum.
- Migrations and seeders perform no network access.

Exit gate:

- Tamper, ambiguity, wrong game, wrong patch/league, incompatible parser,
  expired provenance, and missing activation all fail closed. Fresh migration,
  rollback, and reapply pass on disposable PostgreSQL.

## Prompt 4 — Complete production-grade PoE1 normalization

Dependencies: Prompt 3.

Deliverables:

- Map the already bounded PoB1 format output to the exact approved ruleset
  vocabulary without copying PoB formulas or datasets.
- Implement conservative pasted PoE1 item-text parsing for only the required
  factual fields. Unsupported locale, modifier, tier, or ambiguity remains a
  typed unresolved fact.
- Preserve separate provenance for user input and every canonical mapping.

Exit gate:

- Fixture, hostile-input, size, timeout, checksum, patch mismatch, and unknown
  identifier tests pass. Raw PoB/item text is absent from logs, AI context, and
  long-lived storage.

## Prompt 5 — Build the deterministic PoE1 vertical slice

Dependencies: Prompts 3-4.

Deliverables:

- Select a deliberately narrow set of high-value PoE1 checks and implement
  pure calculations under `src/` with explicit decimal/rounding rules.
- Produce immutable findings with input evidence, rule IDs, ruleset identity,
  certainty, limitations, and canonical hashes.
- Replace the unavailable engine binding only when an exact approved ruleset
  resolves. No network, clock, randomness, database, locale, or AI participates
  in calculations.

Exit gate:

- Unit, fixture, property/boundary, mutation, historical ruleset, byte-stability,
  replay, and network-disabled tests pass. Missing facts produce uncertainty or
  refusal, never zero or inference.

## Prompt 6 — Produce deterministic recommendations and manual recipes

Dependencies: Prompt 5.

Deliverables:

- Implement reproducible objectives, constraints, scoring, tie-breaks,
  alternatives, and evidence-linked rationale.
- Bind the existing manual Trade compiler to checksum-approved PoE1 vocabulary.
  Emit descriptive filters and a generic query-free Trade homepage link only.
- Treat prices and availability as unknown unless optional cached poe.ninja
  evidence is separately enabled; category context never becomes an exact item
  claim or ranking authority.

Exit gate:

- Stable-order, conflicting-goal, unmapped-filter, budget, provenance, and
  policy-denial tests pass. No Trade endpoint, Trade ID, encoded URL, listing,
  automation, or invented filter can appear.

## Prompt 7 — Bind production results to the user experience

Dependencies: Prompts 5-6.

Deliverables:

- Replace fixture result binding with owner-authorized persisted products while
  retaining explicit fixture labels on demos.
- Implement loading, partial, failed, unsupported, policy-blocked, stale, and
  completed states with visible evidence, ruleset, source, and limitations.
- AI, if later approved and explicitly opted into, receives minimum structured
  deterministic products and may return explanation text only.

Exit gate:

- IDOR, XSS, stale-result, accessibility, responsive, deletion/export, AI
  immutability, and no-network deterministic-fallback tests pass.

## Prompt 8 — Harden production operations

Dependencies: Prompts 1-7.

Deliverables:

- Exercise database cache/session/queue operation appropriate to the initial
  Laravel Cloud budget; do not make Redis/Horizon mandatory.
- Validate outbox recovery, bounded retries, artifact pruning, deletion,
  backup/restore, source kill switches, rule rollback by activation, mail,
  scheduler, worker lifecycle, and redacted observability.
- Prove user requests read only local validated source snapshots. External sync
  stays separately scheduled, policy-gated, lock-protected, and default-off.

Exit gate:

- Production configuration checks, queue recovery, backup/restore, secret scan,
  failure injection, and staging smoke tests pass without exposing raw input or
  requiring an optional provider.

## Prompt 9 — Qualify the release candidate

Dependencies: Prompts 1-8.

Deliverables:

- Re-run current GGG/source/privacy/license reviews for the exact release SHA.
- Run the complete repository quality chain, parser-security and policy suites,
  fast and extended evaluations, browser tests, production build/image checks,
  and a real disposable PostgreSQL fresh/rollback/reapply cycle.
- Review every public claim against executable behavior; remove fixture-backed
  availability claims. Record deployment, rollback, on-call, kill-switch, and
  incident evidence.

Exit gate:

- Zero unexplained failures or skipped required gates. The only releasable
  product is a useful PoE1 analyzer with AI and every external source disabled.
  PoE2 remains non-public. Deployment still requires explicit operator approval.

## Cross-cutting risks

| Risk | Control and release evidence |
| --- | --- |
| Unlicensed or stale game facts | Prompt 2 approval, immutable source version/checksum, review expiry, fail-closed activation |
| Fixture UI mistaken for production | Runtime binding tests, fixture labels, README/public-claim review in Prompt 9 |
| PoE2 accidentally public | Prompt 1 server-side denials plus route/browser/cross-game tests |
| PostgreSQL/SQLite divergence | Disposable PostgreSQL migration and constraint suite; exact FK type review |
| AI changes authoritative output | Immutable deterministic products, strict explanation-only schema, equality tests, AI-off fallback |
| User-request egress | Outbound guard, Http fakes, socket/network denial tests, source sync isolated to commands/scheduler |
| Trade policy regression | Deny tests for undocumented paths, no Trade IDs/queries/listings, disabled official search provider |
| Sensitive input leakage | Encryption, bounded retention, redacted logs/exceptions/audits, deletion tests, secret scans |
| Operational cost growth | Database-first Laravel abstractions, optional workers/sources/AI, bounded retention and retry budgets |

## Baseline commands and results

Run before documentation changes on 2026-08-20:

```powershell
composer run quality
npm run test:e2e
php artisan route:list
php -v
composer --version
node --version
npm --version
git diff --check
git status --short --branch
```

Results:

- `composer run quality`: passed. This included strict Composer validation,
  Composer audit (no advisories), Pint, PHPStan (zero errors), repository
  guardrails (681 files), PHPUnit, the fast evaluation suite, clean `npm ci`,
  npm audit (zero vulnerabilities), ESLint, Vue TypeScript, Vitest, Vite build,
  and documentation validation (72 Markdown files).
- PHPUnit: 643 tests discovered; 641 passed with 8,305 assertions; 2 skipped.
  The skipped tests require an explicitly enabled disposable PostgreSQL
  database and cover the real parent FK plus full fresh/rollback/reapply cycle.
- Fast evaluation: 31 cases, passed.
- Vitest: 9 files and 21 tests, passed.
- Playwright: 8 tests, passed, including keyboard/320px overflow, PoE1/PoE2
  edition-aware wizard behavior as it existed at baseline, policy boundary, and
  responsive visual fixtures. Prompt 1 must replace the public PoE2 expectation
  rather than treating this baseline behavior as desired scope.
- Vite production build: passed, 642 modules transformed.
- Route inspection: 98 routes. It confirmed the public dual-game catalog route
  and wizard surface that Prompt 1 must close.
- Toolchain: PHP 8.4.24, Composer 2.10.2, Node 24.14.1, npm 11.12.1.
- PostgreSQL integration unavailable locally: `psql`, `pg_isready`, Docker, and
  Podman were unavailable and TCP port 5432 was closed. No remote database or
  `migrate:fresh` command was attempted. SQLite results are not PostgreSQL proof.
- Existing test failures: none. Existing tests were not edited for this
  baseline. The two PostgreSQL skips remain release blockers, not failures to
  hide or rewrite.

## Documentation review checklist

For every implementation prompt:

- reconcile `AGENTS.md`, ADRs, source register, capability matrix, architecture,
  operations, README files, and `docs/progress.md` with executable behavior;
- preserve historical records while marking superseded scope explicitly;
- run both documentation validators and local-link checks;
- inspect the complete documentation diff for secrets, credentials, private
  data, unsupported availability claims, and copied third-party content; and
- record exact commands, counts, skips, environment limitations, and remaining
  blockers without presenting planned or fixture-backed behavior as shipped.
