# PoE1 live Cloud activation report

## Status

`CLOUD_ACTIVATION_UNVERIFIED` (2026-09-01)

Local Playwright 1.62.1 and the installed Chromium binary now provide real
browser evidence. The live page and unauthenticated PoE1 wizard render and
operate. Synthetic registration reaches `/email/verify`, but no mailbox is
available, so verified login and authenticated analysis remain blocked. Cloud
runtime/CLI authority is unavailable; no production mutation was attempted.

## Deployment

- Live URL: `https://lootwright-production-kt2jq5.laravel.cloud`
- Expected SHA: `564911c1b17e9f2333ebeb209271ac77baec70e2`
- Repository branch SHA: verified with `git ls-remote`; Cloud-served revision:
  unverified.
- Test date: 2026-09-01 (Europe/Istanbul)

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
and the live `policy_denied` PoE1 import gate.

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

The reviewed `PolicyDefaultsSeeder` was already run remotely before this
checkpoint (the live PoE1 import then changed from HTTP 403 `policy_denied` /
`missing_rule` to HTTP 200 `normalized`). No migration, ruleset activation,
QA verification, deployment, or other production mutation was performed as
part of the CLI tooling checkpoint itself. CLI acceptance remains blocked by
the absence of an approved immutable ruleset matching the normalized
acceptance build. The live `/up` endpoint is HTTP 200; `/ready` remains HTTP
404 and `/status` HTTP 403 (the latter is blocked by the Cloud web-server
access rule), pending route-architecture follow-up.
