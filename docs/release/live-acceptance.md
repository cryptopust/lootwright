# Live Acceptance Gate

Status: **BLOCKED** until an authorized operator supplies a dedicated Laravel
Cloud staging environment, disposable test identities, current approved
rulesets, and redacted evidence. This document is a runbook and does not permit
testing against real production records.

## Safety contract

Run `php artisan acceptance:gate --edition=all` from the dedicated staging
environment. The command fails unless `LOOTWRIGHT_RUNTIME_MODE=PRODUCTION_CANONICAL`
and the application environment is non-local/non-testing. `TEST_FIXTURE` runtime,
fixture analyzers, fixture datasets, fake providers, production URLs, and
destructive database commands are prohibited.

Use newly generated test accounts and unique idempotency keys. Record only
opaque IDs, statuses, checksums, timings, and failure codes; never export build
text, prompts, credentials, cookies, or personal data.

## Acceptance matrix

For each edition independently (PoE1 first; PoE2 only when its release gate is
approved), execute the real HTTP/application path:

| Area | PASS evidence | FAIL / DEGRADED / BLOCKED |
| --- | --- | --- |
| Import and parse | Supported real build reaches normalized state with `PRODUCTION_CANONICAL` marker | Fixture marker, parser fallback, invalid state |
| Ruleset and analysis | Exact immutable edition/patch/checksum resolves; findings are persisted | Ruleset unavailable or checksum mismatch = BLOCKED |
| Planner and Trade search | Ranked upgrades and the human-readable manual Trade-filter recipe are persisted from deterministic output; no automated Trade-site search is performed | Missing vocabulary is DEGRADED only with explicit unsupported result; any Trade automation is FAIL |
| Market | Approved local snapshot is fresh and attributed | Provider down/stale data = DEGRADED; no invented prices |
| AI explanation | Explicit opt-in explanation references existing findings only | Provider down/schema failure = DEGRADED with deterministic fallback |
| Persistence | Save, reload, owner authorization, and deletion succeed | Cross-owner access or undeleted data = FAIL |
| Negative cases | Invalid build, wrong edition, unsupported patch, unknown modifier, queue delay, expired data fail closed | Any fabricated fallback = FAIL |
| Auth/admin | Registration, verification, login, saved-analysis isolation, admin denial and health views behave correctly | Unauthorized access = FAIL |

## Required evidence record

Record timestamp, Cloud environment/deployment SHA, runtime marker, PHP/Laravel
versions, edition, ruleset identity/checksum, parser/engine versions, opaque
test identity IDs, queue names, status/failure codes, latency, and redacted
screenshots/log references. Classify every row as **PASS**, **FAIL**,
**DEGRADED**, or **BLOCKED**. A single FAIL blocks release; DEGRADED requires
an explicit product decision; BLOCKED means evidence or an approved capability
is missing. Do not claim PASS from fixture, unit, or evaluation coverage.
