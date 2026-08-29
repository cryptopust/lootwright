# Production Test Matrix

Status: production-hardening matrix, reviewed 2026-08-21. `PASS` means the named
test actually ran in the stated environment. `BLOCKED` is not a pass. Test-only
fixtures are identified explicitly and are not production-data evidence.

## Required gates

| Layer | Command | Required evidence |
| --- | --- | --- |
| Dependencies | `composer validate --strict`; `composer audit`; `npm ci`; `npm audit --audit-level=high` | Lockfiles valid; no reported advisories at the configured threshold |
| PHP quality | `composer run format:check`; `composer run analyse`; `composer run ci:guardrails` | Formatting, static boundaries, policy/secret guardrails pass |
| PHP tests | `composer run test`; `composer run test:architecture`; `composer run test:parser-security`; `composer run test:policy-gate` | No production binding is silently replaced by fixture mode |
| Frontend | `npm run lint`; `npm run typecheck`; `npm run test`; `npm run build` | Lint, TypeScript, component tests, production bundle pass |
| Browser | `npm run test:e2e` | Original synthetic fixture only; no real external HTTP |
| PostgreSQL | CI PostgreSQL 18 service plus `POSTGRES_MIGRATION_INTEGRATION=true php artisan test tests/Feature/PostgreSqlMigrationCompatibilityTest.php` | Empty migrate, FK/trigger assertions, rollback, reapply |
| Documentation | `powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/validate-docs.ps1` | Links, headings, policy terms, and required files pass |
| Diff | `git diff --check`; scoped diff review; final `git status --short --branch` | No whitespace errors, secrets, generated dependencies, or unrelated changes |

All Laravel tests inherit `Http::preventStrayRequests()`. A test that needs an
approved adapter must use `Http::fake()` and assert exact URL, method, and headers.
Inertia SSR is explicitly disabled in tests so view rendering cannot create an
undeclared localhost request.

## Security coverage

| Surface | Test evidence | Current classification |
| --- | --- | --- |
| PoB1/XML/decompression | `PobImportTest`, `BuildImportPipelineTest`, `PobImportEndpointTest`, `SecurityHardeningTest` | PASS: strict base64/compression, size/ratio/time/count/depth bounds, UTF-8, DTD/XXE/network denial, duplicate/cross-edition handling |
| PoB2 | Domain/parser malicious fixtures, golden regressions, and cross-edition tests | ACTIVE beta: public HTTP boundaries accept PoE2 and exact ruleset resolution still fails closed |
| Authentication/session | `AuthenticationFlowTest`, `CatalogAndMembershipTest`, `SecurityHardeningTest` | PASS in feature tests; staging mail/cookie/proxy exercise remains an operations gate |
| Authorization/admin/IDOR | Membership, workflow ownership, privacy-session, policy-admin and recent-password/2FA tests | PASS |
| Source imports/SSRF | `GggPassiveTreeImportTest`, `ExternalSourceArchitectureTest`, Policy Gate tests, source job hardening test | PASS with mocked HTTP: exact allowlists, DNS/private-address/redirect denial, policy gate, uniqueness, overlap, timeout, failure behavior |
| AI | `AiGatewayTest`, `AiApplicationServicesTest`, admin AI feature tests | PASS with fake provider: schema/edition/fact validation, quotas, circuit breaker, AI-off fallback; no live provider call |
| Queues | `AnalysisWorkflowTest`, `SecurityHardeningTest`, source job hardening test | PASS: outbox recovery, atomic claims, duplicate no-op, typed retries, exhausted failures, timeout/unique source job policy |
| Exports/privacy/logs | Analysis workflow/export/deletion tests and log redaction/correlation tests | PASS: owner scope, fixed JSON attachment, no-store, checksum, retention, control-character and secret redaction |
| Browser/XSS/CSRF/redirects | Security header, hostile-text, route middleware, escaped pagination labels, the CI `v-html` prohibition, and Playwright tests | FAIL aggregate gate on 2026-08-21: 7/8 passed; the pre-existing `trade-1440.png` visual baseline differs from current output. Security behavior tests passed; the unrelated snapshot was not rewritten |
| SQL/mass assignment | Parameterized repository/controller queries and guarded-model test | PASS at application-test level; least-privilege production role remains deployment evidence |
| PostgreSQL | SQL compilation tests and CI disposable PostgreSQL job | PASS in CI configuration; BLOCKED locally when no disposable PostgreSQL service is available |

## E2E critical paths

| Critical path | Expected result | Classification |
| --- | --- | --- |
| PoE1 happy path | Approved PoE1 input → exact ruleset → deterministic findings → ordered recommendations/manual recipe | PASS in feature/golden and browser behavior tests; aggregate Playwright remains FAIL because of the separate Trade visual snapshot |
| PoE2 happy path | Public PoE2 analysis | BLOCKED by product release scope; must not be represented as PASS |
| Edition mismatch | Cross-edition input/selection fails visibly | PASS |
| Unsupported ruleset | No silent current-ruleset fallback | PASS |
| AI unavailable | Manual intent and deterministic workflow continue | PASS |
| External source unavailable | Local approved snapshot remains usable or request fails closed; no user-request fetch | PASS |

## Production-only blockers

- A disposable PostgreSQL instance is required for local proof; SQLite results do
  not alter this blocker. CI supplies PostgreSQL 18 and runs the opt-in test.
- Real mail delivery, edge proxy/TLS/cookie behavior, database least privilege,
  queue worker termination, backup-provider restore/deletion replay, and incident
  contacts require a staging or production environment.
- PoE2 production data/ruleset/analyzer approval and public activation are phase
  two work and intentionally blocked.
- The tracked Trade desktop screenshot must be reviewed against the current Trade
  page in a dedicated UI change. Production hardening did not overwrite that
  unrelated visual baseline merely to turn the aggregate E2E command green.

## 2026-08-21 local hardening run

- PHP: 949 tests discovered, 947 passed, 13,116 assertions, two PostgreSQL-only
  tests skipped because no disposable server was present.
- Focused architecture/parser/policy suites: 551/551, 44/44, and 109/109 passed.
- Fast evaluation: 31/31 cases passed; network violations and accepted invented
  canonical identifiers remained zero.
- Frontend: ESLint, TypeScript, 22/22 Vitest tests, and production build passed.
- Playwright: 7/8 passed. The PoE1 scope landing/wizard snapshots were reviewed
  and updated; the unrelated Trade desktop snapshot remains FAIL.
- Dependency/security checks: Composer validation/audit and npm high-severity
  audit passed with no reported vulnerabilities. The clean npm install emitted a
  transitive deprecated `glob@10.5.0` warning that requires dependency review.
- SQLite empty migrate, full rollback, and reapply passed as fast feedback only.
- PostgreSQL: four compilation assertions passed and two real-integration tests
  were skipped. Docker, Podman, `psql`, and `pg_isready` were unavailable and
  localhost port 5432 was closed. PostgreSQL remains locally BLOCKED; CI now has
  an explicit PostgreSQL 18 integration-test step.
