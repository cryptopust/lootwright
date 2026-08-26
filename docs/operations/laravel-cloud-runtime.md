# Laravel Cloud Runtime Reality (2026-08-26)

Status: **UNVERIFIED**. This report records evidence available from the
repository checkout and local commands. Repository documentation is not a
substitute for Laravel Cloud dashboard, deployment-log, or production HTTP
evidence.

## Verification summary

| Area | Status | Evidence / limitation |
| --- | --- | --- |
| Deployment | UNVERIFIED | No Laravel Cloud CLI is installed or authenticated in this workspace. No deployment ID, release SHA, or Cloud dashboard export was available. |
| Public URL and HTTPS | FAILED | The documented primary host `lootwright.org` returned DNS `NXDOMAIN`; HTTP, HTTPS, redirect, and `/up` probes therefore returned no response. Any unlisted Laravel Cloud generated hostname remains UNVERIFIED. |
| Application runtime | PARTIAL | Local `php artisan about --only=environment` reports Laravel 13.25.0 and PHP 8.4.24, but `APP_ENV=local`, debug enabled, and localhost URL; this is not production evidence. |
| PostgreSQL | FAILED (local only) | `php artisan migrate:status` could not connect to local PostgreSQL at 127.0.0.1:5432. Cloud connectivity and migration state are UNVERIFIED. |
| Queue | UNVERIFIED | Cloud worker/managed queue configuration and processing metrics unavailable. Local default configuration is not evidence. |
| Scheduler | PARTIAL | `routes/console.php` defines five bounded, non-overlapping schedules. `php artisan schedule:list` could not complete because local Redis was unavailable; Cloud toggle/heartbeat is UNVERIFIED. |
| Storage | PARTIAL | `analysis-artifacts` uses encrypted local disk. Deployment guidance correctly keeps imports disabled until durable private object storage is configured; Cloud disk configuration is UNVERIFIED. |
| Mail | UNVERIFIED | No Cloud mail-provider settings or delivery probe were available. Do not infer registration, verification, or reset delivery. |
| Backups/restore | UNVERIFIED | No provider schedule, retention record, or isolated restore evidence was available. |
| TLS/domain/proxy | UNVERIFIED | No resolvable Cloud hostname or proxy response was available. Production secure-cookie and trusted-host behavior require a Cloud smoke test. |
| Readiness | PARTIAL | `/up` and token-protected `/ready` routes exist. `/ready?detail=1` now exposes non-sensitive component statuses; no production response was observed. |
| Monitoring/alerting | PARTIAL | Structured redacted logging, failed-job persistence, source-sync records, AI telemetry, and a provider-neutral `queue_job_failed` log event exist. Provider alert routing and observed 5xx/queue/import alerts are unverified. |

## Local evidence collected

The local runtime reports Laravel `13.25.0`, PHP `8.4.24`, and Composer
`2.10.2`. It is explicitly `local` with debug enabled. PostgreSQL and Redis
were not running, so no local command was treated as Cloud evidence. The
Laravel/Cloud CLI is not installed; the available `gh` command is an unrelated
Node package and is not authenticated Cloud access.

The complete Playwright suite runs against the local application and passed
8/8. This validates the checked-in UI fixture only, not the deployed service.

## Readiness contract

`GET /up` is dependency-free and returns plain-text `OK`. `GET /ready` remains
hidden unless the `X-Lootwright-Readiness-Token` header matches the configured
secret and is rate limited. Operators may request `GET /ready?detail=1` with
the same header to receive these statuses without credentials or connection
strings:

`APP`, `DATABASE`, `CACHE`, `QUEUE`, `STORAGE`, `ACTIVE_POE1_RULESET`,
`ACTIVE_POE2_RULESET`, `MARKET_PROVIDER`, and `AI_PROVIDER`.

Each component is one of `HEALTHY`, `DEGRADED`, `DISABLED`, or `FAILED`.
Disabled PoE2/ruleset, market, and AI capabilities are reported as disabled;
they are not treated as a production support claim. A failed dependency keeps
the detailed endpoint at HTTP 503.

## Required Cloud verification runbook

An authorized operator must collect, with secrets redacted:

1. Cloud environment name, deployment status, reviewed commit/release SHA,
   PHP runtime, canonical HTTPS URL, `APP_ENV`, `APP_DEBUG`, trusted hosts and
   proxy configuration.
2. `php artisan migrate:status` and a read-only query confirming required
   ruleset, source, workflow, and `failed_jobs` tables; never use
   `migrate:fresh` or production rollback.
3. Actual queue driver, worker/managed-queue status, failed-job count, retry and
   timeout settings, and one observed successful bounded job.
4. `php artisan schedule:list`, scheduler toggle/heartbeat, and confirmation
   that every scheduled command is idempotent and coordinated.
5. Filesystem/object-storage disk and a disposable artifact lifecycle check;
   imports remain disabled while only ephemeral local storage is available.
6. Mail provider configuration and test delivery for verification/password
   reset, or explicitly record `MAIL_UNVERIFIED`.
7. Backup provider, schedule, retention, and an isolated non-production restore
   test. Never overwrite production data.
8. HTTPS redirect, secure cookies, CSRF/session domain, and `/up` plus
   token-authenticated `/ready?detail=1` responses.
9. Alert visibility for HTTP 5xx, queue failures, dataset imports, analyses,
   database, AI, and market providers.

## Unresolved blockers

- Cloud dashboard/CLI credentials or a redacted deployment export are required
  to verify runtime, database, queue, scheduler, mail, backups, and monitoring.
- A resolvable deployed hostname and authenticated readiness probe are required
  for production HTTP/TLS evidence.
- Durable private artifact storage and an asynchronous execution choice remain
  prerequisites before enabling imports or queued production analysis.
- An approved immutable PoE1 ruleset plus signed staging acceptance remains the
  release gate; PoE2 stays dormant and independently unverified.
