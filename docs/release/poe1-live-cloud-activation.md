# PoE1 live Cloud activation report

## Status

`CLOUD_ACTIVATION_UNVERIFIED` (2026-09-01)

The deployed URL responded over HTTPS, but this workspace had no connected
browser or Laravel Cloud runtime/CLI connector. Registration, authentication,
ruleset activation, CLI acceptance, and the authenticated browser analysis flow
therefore could not be executed without inventing evidence. No production data
was changed.

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

## Unverified gates

QA account creation, email verification, login/logout/re-login, PostgreSQL
configuration and migration status, queue and scheduler state, durable storage,
source-status/import/publish/activation commands, `PRODUCTION_CANONICAL` runtime,
CLI acceptance, browser build analysis, planner constraints, Trade recipes,
save/reload, owner isolation, export/delete, invalid-input handling, responsive
screens, admin denial, password reset, and post-deploy retesting all require a
connected browser and Cloud operator/runtime access.

## Release decision

`CLOUD_ACTIVATION_UNVERIFIED`

The repository remains ready for Cloud activation at commit `564911c`, but the
live application cannot be called `LIVE_POE1_BETA_READY` from this environment.
