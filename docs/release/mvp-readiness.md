# MVP Readiness Review

## Verdict: FAIL

Reviewed on 2026-08-16 against Git `0ccc8ec` plus the release-review changes in
the working tree. This is not a production-ready release.

The implementation, security baseline, parsers, policy controls, persistence
orchestration, fake-provider AI containment, fixture UI, and production package
are internally well tested. The defining PoE1 MVP acceptance criterion still
cannot pass in production: there is no approved immutable PoE1 ruleset source,
ruleset catalog/activation path, or production deterministic analyzer. The
shipping container binds `DeterministicAnalysisEngine` to
`UnavailableDeterministicAnalysisEngine`, so real findings, prioritized
upgrades, and Manual Trade Recipes fail closed. Fixture-backed success tests are
valuable conformance evidence but are not game-accuracy or production evidence.

## Verified evidence

The reviewer read `AGENTS.md`, every Markdown document and ADR, the locally
available Git history, application/domain/adapters, migrations, routes, jobs,
configuration, UI, tests, CI, and production packaging. The worktree began
clean on branch `main`, nine commits ahead of `origin/main`; the reviewed local
history ran from foundation commit `25fd7f2` through packaging commit
`0ccc8ec`.

The following commands passed locally:

```powershell
composer validate --strict
composer audit
composer run format:check
composer run analyse
composer run ci:guardrails
composer run test
composer run test:architecture
composer run test:parser-security
composer run test:policy-gate
composer run eval:fast
composer run eval:extended
npm ci
npm audit --audit-level=high
npm run lint
npm run typecheck
npm run test
npm run test:e2e
npm run build
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/validate-docs.ps1
```

Final evidence was 555 PHPUnit tests with 7,809 assertions, 330 architecture
checks with 6,471 assertions, 39 parser-security tests with 223 assertions, 71
Policy Gate tests with 367 assertions, 31 fast eval cases, 35 extended eval
cases, 15 Vitest tests, and 7 Chromium Playwright tests. The SQLite migration
sanity cycle ran fresh/status/rollback-last/reapply successfully. Documentation
validation covered 63 Markdown files. Composer and npm reported no known
advisories.

An isolated `git archive` bootstrap also passed using the documented
`composer run setup:windows` command with explicit local SQLite/array/sync
verification overrides: Composer installed from `composer.lock`, npm installed
from `package-lock.json`, all migrations ran, Vite built production assets, and
the five foundation HTTP tests passed. Docker, PostgreSQL, Redis, Bash, `psql`,
and `redis-cli` were unavailable on this Windows host. The checked CI workflow
contains PostgreSQL migration and production image/Compose checks, but a passing
CI run and deployment-stage restore exercise are still required for the final
release commit.

## End-to-end acceptance results

| Acceptance case | Result | Evidence and limitation |
| --- | --- | --- |
| Anonymous PoE1 pasted structural PoB, AI disabled, deterministic result and Manual Trade Recipe | **PASS in local harness; FAIL in production** | `AnalysisWorkflowTest::test_release_harness_runs_real_poe1_import_for_anonymous_user_with_ai_off_and_complete_deletion` uses the real bounded parser, real policy-gated intake, persistence, queues/use cases, and deletion with an explicitly fake deterministic engine/ruleset. It verifies strict/broad numeric filters and trace codes. `test_production_analysis_binding_fails_closed_without_an_approved_ruleset_or_analyzer` proves the shipping binding is unavailable. |
| Authenticated flow with constrained AI explanation and identical deterministic recommendation | **PASS with fake AI** | `AnalysisWorkflowTest::test_constrained_explanation_persists_only_codes_from_completed_deterministic_products` now compares the encrypted recommendation record and canonical hash before and after explanation. Normal CI uses no key or live provider. |
| PoE2 input cannot use PoE1 rules | **PASS** | Edition-isolation domain tests, PoB adapter tests, stale/mismatched queue identity tests, and Manual Trade adapter denial tests pass. PoE2 remains beta format-only. |
| Malicious PoB/XML and prompt injection are contained | **PASS** | Parser-security suite covers malformed Base64/zlib/XML, XXE/DTD, invalid UTF-8, depth/count/ratio/time limits, hostile notes, and URL refusal. AI tests reject prompt injection before transport and reject unknown canonical IDs. |
| Denied integrations cannot be invoked by HTTP, job, CLI, or admin surface | **PASS** | Repository guardrails, architecture tests, emergency-switch tests, malformed/stale job tests, CLI local-only path tests, Policy Gate exact-operation denials, and absence of connector/admin UI routes pass. No live Trade or GGG connector exists. |
| Export and deletion complete correctly | **PASS in primary store** | Owner-scoped export hash/timestamp tests and account/anonymous deletion cascade tests pass. Backup expiry and restore-time deletion replay remain deployment blockers. |
| Funding-disabled mode renders no monetization link | **PASS** | Funding application, Vue, browser, guardrail, and donor-input prohibition tests pass; funding is code-disabled and Policy Gate denied. |
| Clean-checkout documented setup | **PARTIAL** | Isolated Windows/SQLite bootstrap passed. Production Docker/PostgreSQL/Redis image, migration, queue, and restore execution require CI/staging evidence unavailable on this host. |

## Fixed defects in this review

- Replaced the stale README claim that no AI provider adapter existed with the
  accurate default-off, Policy-Gate-blocked OpenAI adapter status and explicit
  production-analysis fail-closed boundary.
- Added a real-parser anonymous acceptance flow with a trace-bearing required
  filter, strict/broad numeric ranges, AI-off assertions, persistence, and full
  primary-store deletion.
- Added a production-binding regression proving the application cannot disguise
  the absent approved ruleset/analyzer as a completed result.
- Strengthened the constrained-AI test to prove persisted deterministic
  recommendation code, ciphertext, and canonical hash are unchanged.
- Added `CONTRIBUTING.md`, `SECURITY.md`, and `THIRD_PARTY_NOTICES.md` to make
  contribution acceptance, vulnerability reporting, dependency attribution,
  publisher-data exclusions, and unresolved contact/governance work explicit.

## Remaining blockers by severity

### Critical

1. `POE1-RULES-001` is a disabled candidate with no approved source, version,
   permission scope, checksum, transformation/redistribution analysis, or
   activation implementation. There is no production ruleset row to resolve.
2. The production deterministic analyzer and real PoE1 finding/ranking formulas
   do not exist. Consequently the primary PoE1 MVP workflow and production
   Manual Trade Recipe generation cannot complete.
3. Production findings/upgrades/recipes pages are fixture-backed rather than
   bound to authoritative application results. Shipping them as real advice
   would misrepresent provenance and capability.

### High

1. No production backup provider, tested isolated restore, deletion replay, RPO,
   RTO, or named operator evidence exists.
2. Hosting jurisdiction, privacy controller/security contact, age policy,
   breach timelines, provider terms, and legal/policy release review are unset.
3. The final release commit has not passed the remote CI PostgreSQL migration,
   Docker build/inspection, Compose render, or production preflight jobs.
4. No public account registration/login/recovery flow exists. Anonymous privacy
   sessions are implemented, but account UX and verified-email operation are
   not a public release surface.
5. The current GGG/OpenAI source evidence must be rechecked for the exact release
   SHA and environment. No GGG support correspondence or funding permission is
   present; silence is not approval.

### Medium or low

1. PoE1 pasted item-text import is not implemented as a production analysis
   artifact type.
2. External contributor licensing/trademark governance and a verified private
   vulnerability-reporting contact remain unresolved; outside patches must not
   be merged yet.
3. `npm ci` reports a deprecated transitive `glob@10.5.0` through
   `@vue/test-utils -> js-beautify`; npm reports no current advisory. Track the
   maintained upstream update without bypassing lockfile review.

## Compliance and policy review items

- Re-read the current first-party GGG Developer Docs, API Reference, and Terms
  for the exact release SHA; update retrieval metadata/checksum and expiry.
- Select no game-data source until permission, commercial/derivative use,
  transformation, redistribution, attribution, retention, checksum, and review
  evidence are complete.
- Keep undocumented Trade endpoints, scraping, market listings/prices,
  `POESESSID`, browser/client access, automation, overlays, extensions, and
  protected GGG assets prohibited.
- Keep OpenAI disabled until explicit user opt-in/privacy review, executable
  Policy Gate allow, exact model/schema documentation recheck, project hard
  spend cap, and deployment egress controls are verified.
- Keep funding disabled. There is no GGG approval, OpenAI sponsorship, credit,
  program eligibility, payment provider, or monetization permission.
- Verify the exact non-affiliation notice, original asset inventory, lockfile
  licenses, Path of Building attribution, and third-party notices at release.

## Supported feature matrix

### PoE1 supported now

- bounded local format-only PoB1 XML/Base64/zlib import from explicit user input;
- exact root edition detection, normalized structural facts, warnings,
  unsupported-feature disclosure, parser/source/checksum provenance;
- transient import and owner/anonymous-session scoped persisted workflow,
  encrypted raw handoff, lifecycle states, idempotency, retries, export, and
  deletion;
- deterministic/application contracts, manual-recipe compiler, serializers,
  Policy Gate, and evaluation harness using original fixture facts;
- Turkish/English responsive fixture UI with accessibility and disclosure
  foundations; and
- optional constrained AI architecture with deterministic fallback and fake
  transport tests.

PoE1 production game findings, ranking, and recipes are **not supported** until
the critical ruleset/analyzer blockers are resolved.

### PoE2 supported and unsupported

PoE2 supports only a separately namespaced beta, format-only PoB2 intake and
structural compatibility review from explicit user input. It is visibly labelled
inactive for analysis.

PoE2 rulesets, game datasets, canonical mappings, deterministic findings,
recommendations, Manual Trade Recipes, parity claims, production persistence
claims, and cross-edition comparison are unsupported. PoE1 rules and identifiers
cannot be used as fallback.

## Deployment prerequisites

1. Resolve every critical and high blocker above and change this verdict through
   a new review tied to an immutable release SHA.
2. Run the complete CI workflow, PostgreSQL migration rollback/reapply sanity,
   production image build/scan/attestation, Compose render, and offline
   configuration preflight.
3. Record approved ruleset manifests/checksums, source evidence, activation and
   rollback tests, exact parser compatibility, and deterministic replay hashes.
4. Configure private TLS PostgreSQL/Redis with separate runtime/migration roles,
   secret storage, HTTPS proxy/trusted-host boundaries, and protected readiness.
5. Complete a no-egress backup/restore/deletion-replay exercise and record RPO,
   RTO, operators, security/privacy contacts, and incident notification duties.
6. Deploy initially in lockdown; verify headers, authorization, rate limits,
   queue isolation, scheduler pruning, telemetry redaction, and every emergency
   switch before enabling one reviewed capability at a time.

## Rollback criteria

Immediately disable affected capabilities and roll back to the previous signed
image digest if any parser bound, authorization boundary, deletion guarantee,
policy decision, ruleset checksum, deterministic replay, cross-edition isolation,
secret redaction, migration, queue identity, or CSP/security-header check fails;
if prohibited external behavior or uncontrolled egress appears; or if current
source permission is revoked or ambiguous.

Use the expanded schema with the previous compatible image. Do not automatically
run destructive down migrations. An irreversible schema or integrity failure
requires incident response and isolated restore, not improvised recomputation.

## Next three milestones

1. Approve and implement the immutable PoE1 ruleset catalog/import/activation
   path with exact source permissions, checksums, isolation, and rollback.
2. Implement and independently review one narrow real PoE1 deterministic
   analysis plus prioritized upgrade/Manual Trade vertical slice with golden,
   property, replay, uncertainty, and trace tests.
3. Bind the production UI to owner-scoped application results, then complete
   staging PostgreSQL/Redis/queue/backup-restore/security/policy acceptance and
   repeat this release-owner review on the final SHA.

This product isn't affiliated with or endorsed by Grinding Gear Games in any way.
