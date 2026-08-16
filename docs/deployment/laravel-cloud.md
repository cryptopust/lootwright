# Laravel Cloud Pre-Alpha Staging

Status: staging guide only. Following this guide creates external resources and
must be performed manually by an authorized repository owner. This repository
change does not connect GitHub, create a Cloud application, or deploy anything.

The first hosting target is Laravel Cloud Starter in Europe (Frankfurt), using
Laravel Cloud Serverless PostgreSQL and the free generated `*.laravel.cloud`
environment domain. No custom domain, Reverb, paid Nightwatch requirement,
Valkey, or queue resource is needed for the initial locked-down foundation.

Official references:

- [Source control](https://cloud.laravel.com/docs/source-control)
- [Environments and generated Cloud domains](https://cloud.laravel.com/docs/environments)
- [Deployments](https://cloud.laravel.com/docs/deployments)
- [Scheduled tasks](https://cloud.laravel.com/docs/scheduled-tasks)
- [Compute and Scale to Zero](https://cloud.laravel.com/docs/compute)
- [Pricing and spending controls](https://cloud.laravel.com/docs/pricing)

Resource availability and prices can change. Verify the dashboard and current
official documentation before creating anything; this guide intentionally does
not encode unit prices.

## 1. Connect the repository

1. In Laravel Cloud account settings, connect GitHub and install/authorize the
   Laravel Cloud GitHub App only for the repositories the owner intends to use.
2. Create an application from `cryptopust/lootwright`.
3. Select the current reviewed branch. Do not enable a deploy hook or automatic
   production release as part of this documentation task.
4. Create one environment named `staging` or `pre-alpha`.
5. Select Europe (Frankfurt) when it is offered to the account.

Use the generated Cloud domain. Laravel Cloud assigns a `laravel.cloud` domain
after successful deployment; changing the application or environment name can
change that domain and requires redeployment. A custom domain is neither
required nor recommended for this stage.

## 2. Choose the minimum resources

- Select the smallest Starter application compute suitable for a Laravel 13
  pre-alpha shell.
- Attach Serverless PostgreSQL. Keep the Cloud-injected connection values as
  the authority; do not paste the local `.env.example` database password into
  Cloud.
- Do not create Valkey initially. Database-backed cache and sessions are
  adequate for the locked-down foundation.
- Do not create a managed queue or background worker initially. Imports,
  rulesets, external links, AI, and queued analysis remain disabled.
- Do not add Reverb. The application has no WebSocket requirement.
- Enable Scale to Zero or hibernation only where the selected resource supports
  it and after checking wake behavior. When uninterrupted queued work becomes
  necessary, use a reviewed managed queue rather than relying on an application
  process that may sleep.
- Enable the scheduler only when retention or outbox commands are intended to
  run in this environment. Laravel Cloud runs `schedule:run` every minute when
  its Scheduler toggle is enabled.

## 3. Environment variables

Enter values in the Laravel Cloud environment UI. Values marked **secret** must
come from the platform secret store and must never be committed or copied into
issues, logs, or screenshots.

### Required application settings

| Variable | Staging value | Secret | Notes |
| --- | --- | --- | --- |
| `APP_NAME` | `Lootwright` | No | Display name only. |
| `APP_ENV` | `production` | No | Staging uses production security behavior. |
| `APP_KEY` | Cloud/operator generated Laravel key | **Yes** | Generate once; rotation needs a retained-data migration plan. |
| `APP_DEBUG` | `false` | No | Never enable on the public Cloud hostname. |
| `APP_URL` | `https://<generated-environment>.laravel.cloud` | No | Replace with the exact generated domain, then redeploy. |
| `APP_RELEASE_SHA` | Exact deployed Git commit SHA | No | Do not use `latest` or a branch name. |
| `LOG_CHANNEL` | `stderr` | No | Laravel Cloud collects process output. |
| `LOG_LEVEL` | `info` | No | Logs remain content/secret redacted. |
| `DB_CONNECTION` | `pgsql` | No | Serverless PostgreSQL is authoritative. |
| `CACHE_STORE` | `database` | No | Use while no Valkey resource exists. |
| `SESSION_DRIVER` | `database` | No | Avoid ephemeral file sessions. |
| `SESSION_ENCRYPT` | `true` | No | Required privacy baseline. |
| `SESSION_SECURE_COOKIE` | `true` | No | Generated Cloud domain uses HTTPS. |
| `QUEUE_CONNECTION` | `sync` | No | Safe only while asynchronous product capabilities stay disabled. |
| `FILESYSTEM_DISK` | `local` | No | Temporary framework files only; never durable user artifacts. |
| `READINESS_TOKEN` | Random 32+ character value | **Yes** | Protects `/ready`; never put it in a URL. |
| `DEPLOYMENT_LOCKDOWN_MODE` | `true` | No | Required for first deployment. |
| `POLICY_GLOBAL_KILL_SWITCH` | `true` | No | Denies every gated capability. |
| `IMPORTS_ENABLED` | `false` | No | Local artifact storage is not Cloud-durable. |
| `RULESETS_ENABLED` | `false` | No | No approved production ruleset exists. |
| `EXTERNAL_LINKS_ENABLED` | `false` | No | Enable only through reviewed policy. |
| `OUTBOUND_NETWORK_ENABLED` | `false` | No | No provider egress in initial staging. |
| `OPENAI_ENABLED` | `false` | No | Core boot and health need no provider. |
| `FUNDING_ENABLED` | `false` | No | Funding remains policy-disabled. |
| `HORIZON_DASHBOARD_ENABLED` | `false` | No | Horizon is not a Cloud requirement. |
| `MAIL_MAILER` | `log` | No | No production mail credential is needed. |

Laravel Cloud injects connection fields for attached resources. Do not overwrite
its database host, port, database, username, password, URL, CA, cache, or queue
values with the local examples. Inspect variable names in the environment UI
before changing Laravel connection settings.

`TRUSTED_HOSTS` should become one anchored regex matching only the exact
generated domain after it is known. Do not invent proxy IP ranges. Confirm the
current Laravel Cloud managed-proxy guidance before setting `TRUSTED_PROXIES`;
never use a universal trust value merely to satisfy a preflight script.

### Optional and currently absent

- Leave `OPENAI_API_KEY` absent while `OPENAI_ENABLED=false`. No health, build,
  migration, deterministic fallback, or normal test needs it.
- Do not add `REDIS_*` values until Valkey is attached. Valkey uses Laravel's
  Redis-compatible connection configuration when enabled.
- Do not add queue-worker settings until a Cloud managed queue or reviewed
  background worker exists.
- Do not add AWS/object-storage credentials until a private durable artifact
  disk is reviewed and implemented for Cloud.

## 4. Build and deploy commands

Use lockfile-only installs. A suitable build command based on the repository is:

```bash
composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Configuration, route, and view caches belong in the build phase because they
are release artifacts and failures should stop the deployment before traffic is
switched. They must not make a network call or depend on an OpenAI key.

Use only the database migration as the deploy command:

```bash
php artisan migrate --force
```

Do not blindly append:

- `php artisan optimize:clear`: it removes the optimized artifacts just built;
- `php artisan queue:restart`: no worker exists in the foundation stage, and
  managed queue lifecycle belongs to Cloud;
- `php artisan horizon:terminate`: Horizon is not the Laravel Cloud worker;
- `php artisan storage:link`: deploy-command filesystem changes do not persist,
  and public durable uploads are not part of this stage.

The repository's `composer run setup` command is for local development; it
generates a key and runs migrations, so it is not the Cloud build command.
Likewise, `deploy:check-config` currently describes the stricter self-hosted
Redis/Horizon production profile and must not be used as a Cloud success claim.

## 5. Ephemeral filesystem boundary

Laravel Cloud application compute and deploy-command filesystem changes are not
durable storage. Never store user artifacts, database backups, uploaded builds,
or operational evidence on the application container.

The current `analysis-artifacts` adapter uses encrypted local storage for a
bounded queue handoff. Keep `IMPORTS_ENABLED=false` in Cloud staging until a
reviewed private object-storage adapter is configured and tested across web and
worker processes. The synchronous local fixture command does not change this
production boundary.

## 6. First deployment and verification

1. Review the exact commit and run the complete local/CI quality gate.
2. Confirm the Cloud environment is in lockdown and contains no live provider,
   GGG, payment, or mail credential.
3. Deploy from the selected commit through the Laravel Cloud dashboard.
4. Record the generated domain, set `APP_URL` and anchored `TRUSTED_HOSTS`, and
   redeploy if those values were not available before the first build.
5. Confirm `GET /up` returns plain-text `OK` without a database, Valkey, OpenAI,
   GGG, or other external call. Use `/up` as the initial Cloud health probe.
6. Supply `X-Lootwright-Readiness-Token` manually to test `/ready`. The current
   implementation checks PostgreSQL and a Redis-compatible service; without
   Valkey it will report unavailable. Do not provision Valkey merely to turn
   this optional diagnostic green, and do not expose its token publicly.
7. Confirm the landing page says pre-alpha and retains the exact GGG
   non-affiliation notice.
8. Confirm imports, rulesets, external links, OpenAI egress, funding, and Horizon
   UI remain unavailable.
9. Inspect stderr logs for boot/migration errors without copying content or
   secrets into an issue.

No custom domain is required: the generated HTTPS Cloud domain is sufficient
for this entire stage.

## 7. Queue and scheduler activation later

Before adding a queue:

1. configure durable private artifact storage;
2. choose Cloud managed queue or a reviewed worker/background process;
3. map the existing `default`, `build-parsing`, and
   `deterministic-analysis` queue names;
4. verify job attempts, backoff, timeout, stale-ruleset rejection, failed-job
   storage, and safe duplicate execution; and
5. run the outbox recovery and deletion tests in staging with original fixtures.

When enabling the Scheduler toggle, verify `php artisan schedule:list`, then
confirm retention/outbox commands remain idempotent and do not overlap. Do not
enable scheduled work that depends on a disabled capability.

## 8. Rollback and migration precautions

- Review every migration for locks, table rewrites, and backward compatibility.
- Prefer expand/contract changes. Keep the previous application version
  compatible with the expanded schema.
- Use Laravel Cloud's deployment history to select the previous known-good
  deployment when rollback is required.
- Do not automatically run `migrate:rollback` during application rollback.
- If a migration is irreversible or integrity is uncertain, restore only into
  isolation and follow the backup/restore and incident-response runbooks.

## 9. Cost control

- Set the operator's initial monthly target to USD 20 and configure an absolute
  Laravel Cloud spending ceiling of USD 25 where the account supports it.
- Treat the limit as a control, not an exact bill forecast or guarantee.
- Start with application compute plus Serverless PostgreSQL only.
- Keep Valkey, managed queues, background workers, object storage, Reverb, paid
  observability, and custom domains absent until justified.
- Enable Scale to Zero/hibernation where supported and operationally safe.
- Review usage after each staging session and pause nonessential resources from
  the dashboard when testing stops.
- Keep OpenAI disabled. If a later reviewed stage enables it, its application
  circuit breaker and provider project limit are separate from the USD 25 Cloud
  ceiling.

