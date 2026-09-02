# PoE1 live Cloud activation report

## Status

`NOT_READY` (2026-09-02; managed workers and authenticated PoE1 vertical slice verified, ownership gate still pending)

Local Playwright 1.62.1 and the installed Chromium binary now provide real
browser evidence. The live page and unauthenticated PoE1 wizard render and
operate. Synthetic registration reaches `/email/verify`, but no mailbox is
available, so verified login and authenticated analysis remain blocked. Cloud
runtime/CLI authority is verified; the policy defaults were seeded earlier,
but the production workflow remains fail-closed without an approved immutable
PoE1 ruleset matching the acceptance build.

## Deployment

- Live URL: `https://lootwright-production-kt2jq5.laravel.cloud`
- Expected SHA: `564911c1b17e9f2333ebeb209271ac77baec70e2`
- Repository branch SHA: verified with `git ls-remote`; Cloud-served revision:
  unverified.
- Test date: 2026-09-01 (Europe/Istanbul)
- Current Cloud CLI deployment: `3e745c36b01437f9fcf00bd70929b035cbfe699b`
  (succeeded; documentation-only)

## Safe HTTP observations

- `/` returned HTTP 200 and title `lootwright`.
- `/up` returned HTTP 200.
- `/ready` returned HTTP 404; no private readiness payload was exposed.
- `/login` and `/register` returned HTTP 200.
- HTTPS, Cloudflare termination, CSP, `X-Frame-Options: DENY`,
  `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`, and
  `Permissions-Policy` were observed.
- Session cookies were marked Secure, HttpOnly, and SameSite=Lax. Cookie
  values were not retained or reported.
- `/status` returned HTTP 403.
- Chromium navigation to `/` returned HTTP 200 with a readable title and h1,
  no failed document requests, and no page errors.
- The live wizard reached the PoE1 build-information step at `/analyses/new`.
- Repository-owned PoE1 XML submitted from the wizard received controlled
  `POST /api/build-imports/pob` HTTP 403 `policy_denied`. A direct safe replay
  captured policy reason `missing_rule` for `USER-POB-001 / import /
  user_input.pob_code.import`; this is the exact database Policy Gate decision,
  not an emergency switch or parser failure. The reviewed remedy is an
  idempotent `PolicyDefaultsSeeder` run in Cloud, followed by a retest.
- Registration `POST /register` redirected to `/email/verify`
  (`MAIL_VERIFICATION_BLOCKED` without mailbox access).
- Browser console reported CSP violations for inline challenge script/style;
  no failed asset was observed.

## Unverified/blocked gates

QA account creation completed through the public UI, but email verification,
login/logout/re-login, PostgreSQL
configuration and migration status, queue and scheduler state, durable storage,
source-status/import/publish/activation commands, `PRODUCTION_CANONICAL` runtime,
CLI acceptance, browser build analysis, planner constraints, Trade recipes,
save/reload, owner isolation, export/delete, invalid-input handling, responsive
screens, admin denial, password reset, and post-deploy retesting all require a
connected browser and Cloud operator/runtime access.

## Tooling

- `npx playwright --version`: 1.62.1; Chromium was already installed.
- Guarded suite: `npm run test:e2e:live` with
  `LOOTWRIGHT_LIVE_E2E=true`; login additionally needs runtime credentials.
- Live suite: 2 passed, 1 skipped (credential-gated login).

## Release decision

`CLOUD_ACTIVATION_UNVERIFIED`

The repository remains ready for Cloud activation at commit `564911c`, but the
live application cannot be called `LIVE_POE1_BETA_READY` from this environment.
Remaining blockers: verified QA mailbox, Cloud deployment SHA/runtime access,
  and the missing approved immutable ruleset for the normalized acceptance
  build. The policy gate itself is now allowing local PoE1 import.

## Follow-up Cloud CLI verification (2026-09-01)

The official global `laravel/cloud-cli` package v0.5.2 is installed. The
`cloud` executable is available from Composer's global bin directory and the
authenticated account can enumerate applications and environments.

Cloud positively identifies application `lootwright`, environment
`production`, and hostname
`lootwright-production-kt2jq5.laravel.cloud`. The current successful
deployment is `6a3ff3d21bdf80c13210d18e3b53f36b04de6b8e` (`docs: record Cloud CLI
availability and live status`). Remote `command:run` execution is proven.
`php artisan about --only=environment` reports Laravel 13.25.0, production,
debug OFF, and the expected URL. `php artisan migrate:status` reports every
repository migration applied through
`2026_08_29_130000_create_user_saved_records`; `php artisan db:show` proves
PostgreSQL 18.6 over `pgsql` with the expected ruleset, policy, analysis, and
user tables. `queue:failed` reports no failed jobs, and `schedule:list` shows
the four configured pruning/outbox schedules. Bounded Cloud logs are readable.

The reviewed `PolicyDefaultsSeeder` was executed remotely through Cloud and
completed successfully. The official pinned PoE1 source validated (7 classes,
3390 nodes, upstream SHA-256
`7e9f755e33152129ebf36c2ebdad639c527e4ad70d274b1fefb860f30ca01122`). A
candidate was published and activated as immutable ruleset
`01a05dca-26e5-7329-8e99-3ed46be85e58`, version
`3.29.1-analysis.1.0.0.skilltree.8bd138b3`, parser `1.0.0`, ruleset checksum
`6d5b31892ee364afba6d73b964ecf3c402b74faff31c25ddbe227a2550d4829e`.
Activation history is present and the previous ruleset history was not
deleted. The hard Cloud acceptance command still fails closed with
`No approved immutable ruleset exactly matches the normalized PoE1 build.`;
therefore live analysis and authenticated UI activation are not claimed.

Cloud `route:list` confirms `/ready`, `/status`, and the PoE1 import/analysis
routes are registered. `/up` is HTTP 200, `/ready` HTTP 404, and `/status`
HTTP 403 at the Cloud edge. `queue:failed` reports no failed jobs and
`schedule:list` reports the four existing pruning/outbox schedules. The
guarded live Playwright suite passes its two unauthenticated tests (the login
test remains credential-gated and skipped).

## Continued activation (2026-09-01)

Commit `50aa17468ade5fd9f669630864298355b6ec9bd2` deployed successfully.
Cloud acceptance passes with `PRODUCTION_CANONICAL`, the real active ruleset,
deterministic analyzer, planner, and manual recipe engine. Root cause was stale
serialized `GameRuleset` cache entries hydrating as `__PHP_Incomplete_Class`;
ruleset rows now come directly from PostgreSQL.

A disposable QA account was registered through the public form, verified with
the operator-only Cloud command, and logged in successfully. Live PoE1 import
returned HTTP 200 `normalized`; wizard submission returned HTTP 202 `queued`.
The analysis remained queued: Cloud showed one pending database job, no failed
jobs, and no pending workflow-outbox records. Therefore live deterministic
analysis, planner/recipes, save/reload, ownership isolation, and authenticated
E2E remain unverified. This is an operational P1 blocker, not a policy or
ruleset compatibility failure.

## Queue runtime investigation (2026-09-01)

The pending record was a Laravel database-queue analysis job. Its safe metadata
matched the expected `deterministic-analysis` queue; the `jobs` table is
PostgreSQL `public.jobs` with the standard Laravel schema, and timestamps were
immediately available (no future delay or reservation). Effective Cloud config
reported `queue.default=database`, `database.queue=default`, and
`after_commit=true`; per-job routing is `deterministic-analysis`.

The Cloud background-process inventory initially contained workers for both
`build-parsing` and `deterministic-analysis` with the correct connection and
queue, but the queued job was not consumed automatically. A bounded explicit
worker using the exact connection and queue consumed the job successfully,
proving dispatch, schema, timing, serialization, and database connectivity.
The managed analysis process was then updated through the supported Cloud CLI
with the exact bounded command and a replacement process was created to force a
fresh worker revision. Queue monitoring subsequently reported zero pending,
delayed, or reserved jobs. This classifies the incident as
`MANAGED_WORKER_NOT_RUNNING` / `STALE_WORKER_DEPLOYMENT` (Cloud process
lifecycle), not a connection or queue-name mismatch.

The deployed application revision remains `bae8ebf45efe3f82eceb32015ce90be5a61a47cc`.
The worker payload is deployment-safe: scalar IDs only, with PostgreSQL
rehydration of the current ruleset. No job encryption or after-commit defect was
observed, and `queue:failed` remains empty.

An operator-only, data-free queue probe was added for both production queues.
Automatic parsing, automatic analysis, idle-worker, and post-deployment probe
results remain required before this report can promote the release status.

## Final managed-worker verification (2026-09-02)

Cloud identifies application `lootwright`, environment `production`, and the
expected hostname. The latest successful deployment is
`66501ba5b90e1778b9b9b1cd2d2d8e051c8c2558` (`fix: normalize standard league
selection`); this supersedes the previously recorded `fee2c0a` deployment.
The production environment reports Laravel 13.25.0, PHP 8.5.9, debug OFF,
and status `running`.

The managed background-process inventory contains exactly two workers (one
process each), both attached to production and with no stale duplicate:

- parsing: `php artisan queue:work database --queue=build-parsing --tries=3 --backoff=30 --sleep=3 --rest=0 --timeout=300 --quiet`;
- analysis: `php artisan queue:work database --queue=deterministic-analysis --tries=3 --backoff=30 --sleep=3 --rest=0 --timeout=300 --quiet`.

Operator probes were dispatched through Cloud `command:run` only; no
`queue:work` command was run. Parsing probe
`01a062ae-36da-72e1-9cb7-b884f61515d5` reached queued → started → completed
(`2026-09-02T15:12:59Z` → `15:13:01Z`). Analysis probe
`01a062ae-ef41-7304-9c5c-46dfc290cf60` reached queued → started → completed
(`15:13:46Z` → `15:13:47Z`). A further analysis probe after an idle interval,
`01a062b0-a1f2-71dc-b185-c07a02ec7e37`, was automatically consumed as well.
Cloud reports no failed jobs.

The real QA PoE1 workflow completed without manual intervention:
`IMPORT_ACCEPTED` HTTP 202, states `queued → processing → completed`, analysis
`01a062b1-d19b-709f-bc1c-e3cc6b168372`. The result is deterministic PoE1,
engine `1.0.0`, active ruleset
`01a05dca-26e5-7329-8e99-3ed46be85e58`, version
`3.29.1-analysis.1.0.0.skilltree.8bd138b3`, checksum
`6d5b31892ee364afba6d73b964ecf3c402b74faff31c25ddbe227a2550d4829e`.
The normalized summary exposes Duelist/Slayer level 96, mana and capped
fire/cold/lightning resistances, chaos resistance, and the Cleave main skill.
Planner and recipe arrays were empty for this healthy fixture; no market data,
Trade IDs, URLs, prices, or AI output were present.

Save/reload/export behavior was exercised for the QA-owned analysis: save
returned 201, saved listing returned the same analysis, export returned 200
JSON, and unsave returned 200. Ownership-isolation view/API/export checks
against QA User 2 returned 404; cross-owner delete returned 423 (password
confirmation), and no User 1 data changed. Owner password-confirmed deletion
and persistence checks remain follow-up beta checks. Production failure
injection was not run
(`PRODUCTION_FAILURE_INJECTION_NOT_RUN`).

Health endpoints remain `/up` 200, `/ready` 404, and `/status` 403. Cloud
`route:list` previously confirmed both routes are registered; the latter two
responses are treated as edge/private-readiness behavior and are not weakened.

## Controlled semantic variants (2026-09-02)

Using the same live PoE1 workflow and managed workers, the healthy baseline
completed with no findings. A FireResist 50/75 mutation produced
`defence.fire_resistance.below_reported_max`, a traced recommendation, and one
manual POE1 recipe. A Strength 20/100 mutation produced
`attributes.requirement.missing`, a traced recommendation, and one manual POE1
recipe. Both results retained the active ruleset identity and had no market or
AI data. Canonical IDs were resolved from the active snapshot as CI `11455`
and RT `31961`. Deployment `ff41eed5d41ccf82061e35c6774291987124ae4b`
normalized them to `passive:11455` and `passive:31961`; a display-name matching
correction in descendant `1bf9653` still requires deployment and live retest.
Owner-confirmed deletion was completed for disposable analysis
`01a0632e-5cdd-7189-b7cd-3749192e7237`; after the Fortify confirmation form,
subsequent detail/API/export reads returned 404 and the saved listing no
longer contained the record. CI/RT semantic gates remain pending the descendant
deployment and retest.

## Final PoE1 beta gates (2026-09-02)

Deployment `depl-a2a6f522-67d5-46f7-a043-9b24df3438b6` succeeded with commit `1bf965335da3f5222c83d19bb721c3f6303776ca`, matching local HEAD. Both managed workers remained healthy and queues were empty after testing (pending 0, reserved 0, failed 0); no manual `queue:work` was used for live analyses.

Canonical snapshot IDs are CI `11455` and RT `31961`. Live numeric-node probes recognized both; CI suppressed generic life findings/recommendations and RT suppressed crit-dependent output. Low resistance (50/75) and Strength 20/100 variants produced deterministic findings, actionable recommendations, and POE1 manual recipes. Healthy baseline completed with no findings, valid for the control.

Owner-confirmed deletion completed through Fortify password confirmation. Detail/API/export/saved-list reads and post-refresh access were unavailable after deletion. QA User 2 was denied access to the QA User 1 actionable analysis. Save/reload preserved identity, ruleset, findings, recommendations, and recipe. The permanent guarded live suite has nine tests; destructive delete requires `LOOTWRIGHT_LIVE_E2E_DESTRUCTIVE=true`.

`/up` is 200. `/ready` with the DPAPI token is 200 and reports healthy database, with queue/storage degraded and PoE2/market/AI intentionally disabled. `/status` is 403 at the Cloud edge. This remains `EXPECTED_CLOUD_EDGE_READINESS_PROTECTION`; production failure injection was not run.

Decision: `LIVE_POE1_BETA_READY_WITH_LIMITATIONS` with documented limitations for PoE2, AI, market providers, remote fetching, incomplete advanced vocabulary, damage simulation, and Atlas/meta mechanics.
