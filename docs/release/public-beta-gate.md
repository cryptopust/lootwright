# Lootwright public-beta gate

**Review date:** 2026-08-31
**Reviewed revision:** `a423755` (`docs: reconcile documentation with production`)
**Overall status:** **NOT_READY**

This gate is based on repository evidence and commands run in this checkout. It
does not claim that Laravel Cloud, a production database, external providers,
or a real-player staging run were observed. No production data was modified.

## Status vocabulary

- **PASS** — the release requirement is implemented and evidenced for this
  revision.
- **DEGRADED** — the path is safe and usable with an explicit limitation.
- **BLOCKED** — evidence or an external prerequisite is missing; the system
  must fail closed.
- **FAIL** — a release requirement is violated or an unsafe result is possible.

## Gate results

| Subsystem | Status | Evidence |
| --- | --- | --- |
| Authentication, verification, sessions | PASS | `tests/Feature/AuthenticationFlowTest.php`; full backend suite passed (1,067 tests, 1,065 passed, 2 skipped). |
| Authorization, saved records, deletion/export | PASS | Owner-scoped feature tests cover IDOR denial, save/reload/delete, export hashes, and cascades; no production data was used. |
| Rate limits, CSRF, security headers, hostile input | PASS | Parser/security suite passed (32 tests, 153 assertions); red-team regressions cover XXE, decompression bombs, Unicode, XSS, SSRF, queue replay, mass assignment, and privilege boundaries. |
| PostgreSQL migrations | BLOCKED | `php artisan migrate:status --no-ansi` could not connect to `127.0.0.1:5432` (connection refused). PostgreSQL CI/Cloud evidence is required; no destructive migration command was run. |
| Laravel Cloud deployment/runtime | BLOCKED | No authenticated Cloud dashboard/CLI, deployment SHA, or readiness response is available in this workspace. See `docs/operations/laravel-cloud-runtime.md`. |
| Queues and failed-job handling | BLOCKED | Queue contracts, retries, idempotency, and failed-job persistence are tested locally; managed Cloud worker execution and observed queue health are unverified. |
| Scheduler coordination | DEGRADED | Scheduled operations are bounded and coordinated in `routes/console.php`; Cloud scheduler heartbeat and `schedule:list` could not be verified without the managed runtime. |
| Durable object storage | BLOCKED | Import storage is encrypted and abstracted, but a private durable Cloud disk has not been verified. Imports requiring persistence must remain disabled until configured. |
| `/up` and protected readiness | PASS | `ReadinessTest` covers dependency-free `/up`, token-protected `/ready`, component statuses, and failure semantics. A live Cloud response remains unobserved. |
| Logging and telemetry | PASS | Redacted structured HTTP, queue, analysis, market, dataset, and AI telemetry is implemented and static analysis/tests pass; provider alert routing is unverified. |
| PoE1 build import and parse | DEGRADED | Real bounded PoB1 parser/normalizer and item-text boundaries pass tests. A dedicated real-player Cloud acceptance record is still missing. |
| PoE1 canonical ruleset | BLOCKED | No operator-approved immutable production PoE1 ruleset/checksum is present in this checkout; production analysis fails closed. |
| PoE1 deterministic findings | BLOCKED | The canonical production engine binding exists and is fixture-free, but it cannot produce a production result without the approved ruleset. |
| PoE1 upgrade planner and Trade recipe | BLOCKED | Deterministic planner/recipe contracts and tests pass, but no production analysis can currently persist authoritative recommendations/recipes. |
| PoE2 import and parser isolation | DEGRADED | Independent PoB2 adapter and isolation tests pass; public activation remains separately gate-controlled. |
| PoE2 canonical ruleset/analysis/planner | BLOCKED | Independent code and dataset importer exist, but immutable activation, approved evidence, and real-player acceptance are absent. PoE1 rules are never used as fallback. |
| Trade output | DEGRADED | Manual human-readable filters are deterministic and contain no Trade API IDs, links, scraping, or automation. Market/Trade provider integrations are disabled or unverified. |
| Market data | DEGRADED | Cache expiry, provenance, stale-data handling, and provider-down behavior are tested. `poe.ninja` is disabled by default and no live observation was verified. |
| AI assistant | DEGRADED | Closed-schema intent/explanation gateway, prompt-injection refusal, budgets, retries, and deterministic fallback pass tests. OpenAI is opt-in/default-off and no live provider evidence exists. |
| Responsive Turkish/English UI | PASS | Vitest (10 files, 23 tests), Playwright (8 tests), lint, typecheck, and production Vite build passed. Screens validate the checked-in application paths; live deployment remains unverified. |
| Dependency and documentation gates | PASS | Composer/npm audits reported no advisories; `composer validate --strict`, Pint, PHPStan, and `php scripts/validate-docs.php` passed (107 Markdown files). |

## Test evidence

The following local gates passed for this revision:

```text
composer validate --strict
composer audit --format=summary       (no advisories)
vendor/bin/pint --parallel
composer run analyse
composer run test                      (1,067 tests; 1,065 passed; 2 skipped)
architecture tests                     (623 passed)
parser/security tests                  (32 passed)
integration-oriented tests             (35 passed)
npm audit --audit-level=high           (0 vulnerabilities)
npm run lint
npm run typecheck
npm run test                            (10 files, 23 tests)
npm run build
Playwright                              (8 passed)
php scripts/validate-docs.php           (107 Markdown files)
```

The PostgreSQL validation command was attempted and is **BLOCKED** solely by
the unavailable local server. SQLite test success is not used as PostgreSQL
evidence. Cloud, mail, backup/restore, managed queue, scheduler, object
storage, provider, and real-player acceptance evidence must be collected in a
dedicated non-production environment with disposable identities.

## Release decision

The repository has no remaining known critical/high defect that can be safely
fixed without external authority. The overall result is **NOT_READY** because
the required approved ruleset, PostgreSQL/Cloud runtime evidence, durable
storage verification, and signed real-player acceptance are external release
prerequisites. The application must remain fail-closed for unavailable
rulesets/providers and must not be advertised as a live PoE1 or PoE2 analyzer
until those gates are recorded.

## Required external evidence before promotion

1. Deploy this revision to a dedicated Laravel Cloud staging environment and
   record the deployment SHA, HTTPS URL, `/up`, and token-authenticated
   `/ready?detail=1` responses.
2. Run `php artisan migrate:status --no-ansi` against managed PostgreSQL and
   perform a non-destructive rollback/reapply validation in staging.
3. Configure and verify private durable object storage, managed queue workers,
   scheduler execution, logs/alerts, and backup/restore with deletion replay.
4. Import and activate a reviewed immutable PoE1 ruleset (and PoE2 ruleset
   independently) with provenance, checksum, parser compatibility, and
   rollback evidence.
5. Execute `docs/release/live-acceptance.md` with dedicated identities for
   PoE1 and PoE2 independently, including negative, authorization, admin,
   provider-down, queue-delay, expired-data, save/reload/delete, and AI-fallback
   cases. Store only redacted opaque evidence.

This product isn't affiliated with or endorsed by Grinding Gear Games in any way.
