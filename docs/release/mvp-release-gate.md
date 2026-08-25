# Lootwright MVP release gate

Date: 2026-08-24

Overall status: **FAIL**

This verdict is based on production bindings and persisted product behavior,
not on architecture-only support or fixture-backed unit coverage. PoE1 and PoE2
are evaluated independently.

## Independent verdicts

| Edition | Status | Evidence-based conclusion |
| --- | --- | --- |
| PoE1 | **FAIL** | Production PoE1 now maps immutable goals/budget into typed intent, runs the deterministic planner, persists ranked recommendations and decision traces, and records manual recipes as validated filters or explicit unsupported results. Signed staging, PostgreSQL proof, and approved market/vocabulary evidence are still absent. |
| PoE2 | **FAIL** | Separate hostile-input parsing and edition-isolation architecture exist, but PoE2 is outside the public edition allowlist and has no approved active canonical ruleset, validated deterministic rule registry, upgrade candidate factory, or production Trade vocabulary. The application correctly fails closed instead of claiming player analysis support. |

PoE2's failure does not prevent PoE1 from receiving a future independent PASS.
The aggregate status follows the active PoE1 release scope; it does not require
the dormant phase-two PoE2 adapter to pass.

## Critical-path classification

| Critical path | PoE1 | PoE2 | Evidence |
| --- | --- | --- | --- |
| Real build import | BLOCKED | FAIL | PoE1 production importer handles supported PoB1 input with hostile-input bounds, but no signed real-player staging run exists. Fixture-backed execution is parser evidence, not real-build acceptance. PoE2 has no public production path. |
| Edition identification/isolation | PASS | PASS | Parser/domain isolation exists; the public endpoint rejects PoE2 instead of routing it through PoE1. This is safe isolation, not PoE2 feature availability. |
| Exact ruleset resolution | PASS_WITH_LIMITATIONS | FAIL | PoE1 resolves only an operator-activated approved immutable ruleset matching patch/parser. Fixture imports are rejected as production authority. No approved PoE2 ruleset exists. |
| Deterministic findings | PASS_WITH_LIMITATIONS | FAIL | PoE1 production engine persists traceable findings for supported evidence. Rule coverage is intentionally narrow and missing mechanics remain unsupported. |
| Upgrade planner in production journey | PASS_WITH_LIMITATIONS | FAIL | Production analysis runs the edition-scoped planner with typed intent, budget, hard item-preservation constraints, dependency ordering, and persisted candidates. Market-dependent candidates remain uncertain without approved observations. |
| Manual Trade recipes in production journey | PASS_WITH_LIMITATIONS | FAIL | Production persists a recipe per candidate. Exact approved vocabulary produces manual filters; missing vocabulary produces an explicit unsupported recipe and never a guessed modifier or ID. |
| Market-aware budget handling | FAIL | FAIL | Governed poe.ninja economy ingestion exists behind policy/config, but approved observations are not connected to production upgrade planning. No current price is invented. |
| AI-off deterministic operation | PASS_WITH_LIMITATIONS | FAIL | Findings, planner output, decision traces, and safe recipe refusals run with AI disabled. Market-dependent advice remains explicitly uncertain when no approved snapshot exists. |
| AI authority red team | PASS_WITH_LIMITATIONS | PASS_WITH_LIMITATIONS | Fake-provider tests reject unknown recommendations, wrong editions, invented canonical names/codes, numeric claims, price/Trade content, and prompt injection. Staging red-team sign-off is not recorded. |
| Fixture-free production dependency | PASS_WITH_LIMITATIONS | FAIL | PoE1 binding is `ProductionPoe1DeterministicAnalysisEngine` and requires approved imported data. The repository still needs a staging proof against production infrastructure. |
| Critical security suite | BLOCKED | BLOCKED | The local full suite passed on PHP 8.4.24 (984 tests, 982 passed, 2 skipped, 13,848 assertions), and both dependency audits reported no advisories. A reviewed CI/staging security acceptance record for this exact release is still absent. |
| PostgreSQL migration proof | BLOCKED | BLOCKED | The local environment has no reachable PostgreSQL service. SQLite success is not PostgreSQL evidence. |
| Staging player acceptance | BLOCKED | BLOCKED | No signed manual run using the checklist in `mvp-acceptance-manual.md` is present. |

## Traceability status

Findings already contain stable finding/rule identities, ruleset version,
evidence, affected entities, source provenance, confidence, unsupported data,
dependencies, and an explanation trace. The required end-to-end recommendation
chain now continues through the production planner and recipe boundary:

```text
user goal -> finding -> evidence -> rule -> upgrade candidate -> constraints -> market evidence/uncertainty -> recommendation
```

Every node retains the same edition and exact ruleset identity. A recipe is
either a validated manual filter set or an explicit unsupported-vocabulary
result.

## Coverage and release dashboard

The internal page `/admin/release` and command below report, independently per
edition:

- active approved ruleset and public scope;
- dataset coverage by canonical category;
- observed parser adapter;
- deterministic rule count without pretending unknown denominators are 100%;
- production recommendation and recipe persistence;
- unsupported mechanic rate when readable completed samples exist;
- observed end-to-end import/analysis timings;
- market provider state, structural regression report, and aggregate AI health.

The dashboard does not expose raw PoB, item text, prompts, user identities,
secrets, or cookies. It does not turn unit fixtures into player acceptance.

```powershell
composer run test:acceptance
php artisan release:check-mvp --json --write
```

## Verification executed on 2026-08-24

- `composer validate --strict`: passed.
- `composer audit --locked`: passed, no advisories.
- `composer run format:check`: passed.
- `composer run analyse`: passed.
- `composer run test`: passed, 984 tests with 982 passed, 2 skipped, and 13,862
  assertions.
- `composer run test:acceptance`: passed, 6 tests and 60 assertions. The
  representative journey persists ranked recommendations, decision traces,
  and explicit safe recipe outputs; staging and approved vocabulary evidence
  are still required for release.
- `npm ci`: passed.
- `npm audit --audit-level=high`: passed, zero vulnerabilities.
- `npm run lint`, `npm run typecheck`, `npm run test`, and `npm run build`:
  passed. The frontend suite ran 23 tests. The production build took 35.31
  seconds, including 33.2 seconds in the Wayfinder build hook; this is a CI
  build-time observation, not player analysis latency.
- `scripts/validate-docs.ps1` and `git diff --check`: passed.
- PostgreSQL migration/rollback and browser staging acceptance: not run because
  no PostgreSQL service or staging deployment was available in this workspace.

## Exact remaining blockers

1. Import and approve sufficient edition-scoped modifier and Trade vocabulary,
   then generate manual recipes only for validated requirements. Unknown
   filters must remain unsupported.
2. Connect approved, timestamped market observations to budget evaluation; do
   not block structural advice or invent prices when observations are absent.
3. Execute and sign the real PoE1 staging checklist with representative builds
   and player questions, including locked equipment and AI red-team attempts.
4. Run `migrate:fresh`, rollback/reapply, and the full suite against PostgreSQL.
5. For PoE2, independently provide an approved canonical dataset/ruleset,
   verified build representation, deterministic rules, candidate factory,
   Trade vocabulary, production binding, and staging acceptance before adding
   it to the public edition allowlist.

Until blockers 1–8 are closed, PoE1 remains **FAIL**. PoE2 remains **FAIL**
until blocker 5 is completed independently.
