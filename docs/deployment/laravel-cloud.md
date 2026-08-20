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

Set the application runtime to:

- PHP 8.4
- Node.js 24

- Select the smallest Starter application compute suitable for a Laravel 13
  pre-alpha shell (the smallest viable Flex application compute), and enable
  Scale to Zero.
- Attach Serverless PostgreSQL and enable its Scale to Zero setting. Keep the
  Cloud-injected connection values as the authority; do not paste the local
  `.env.example` database password into Cloud.
- Do not create Valkey initially. Database-backed cache and sessions are
  adequate for the locked-down foundation.
- Do not create a managed queue or background worker initially. Imports,
  rulesets, external links, AI, and queued analysis remain disabled.
- Do not add Reverb. The application has no WebSocket requirement.
- Check cold-start behavior after enabling Scale to Zero. When uninterrupted
  queued work becomes necessary, use a reviewed managed queue rather than
  relying on an application process that may sleep.
- Enable the scheduler only when retention or outbox commands are intended to
  run in this environment. Laravel Cloud runs `schedule:run` every minute when
  its Scheduler toggle is enabled.

## 3. Environment variables

Enter values in the Laravel Cloud environment UI. Values marked **secret** must
come from the platform secret store and must never be committed or copied into
issues, logs, or screenshots.

### Required initial application settings

Enter this initial profile. Generate `APP_KEY` and `READINESS_TOKEN` as distinct,
random secrets in Laravel Cloud; the placeholders below are not literal values.

```dotenv
APP_NAME=Lootwright
APP_ENV=production
APP_KEY=<generated-secret>
APP_DEBUG=false
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
LOG_LEVEL=info
DB_CONNECTION=pgsql
DB_SSLMODE=require
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
MAIL_MAILER=log
READINESS_TOKEN=<generated-secret>
VITE_APP_NAME=Lootwright
```

Laravel Cloud automatically configures stderr logging. Do not force
`LOG_CHANNEL` in source or add local file logging as a Cloud requirement; allow
Cloud's injected logging configuration to select the existing `stderr` channel.
That channel uses `php://stderr` and the application sensitive-data redactor.

When Serverless PostgreSQL is attached, do not manually enter any of these:

```dotenv
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

Laravel Cloud injects those values. Never copy local credentials from
`.env.example`, and never commit real Cloud credentials.

### Required pre-alpha lockdown settings

The application currently has an encrypted local artifact handoff that is not
durable on Cloud. Until private object storage and asynchronous workers are
implemented and reviewed, keep the feature and outbound capability switches off:

```dotenv
DEPLOYMENT_LOCKDOWN_MODE=true
POLICY_GLOBAL_KILL_SWITCH=true
IMPORTS_ENABLED=false
RULESETS_ENABLED=false
EXTERNAL_LINKS_ENABLED=false
OUTBOUND_NETWORK_ENABLED=false
OPENAI_ENABLED=false
FUNDING_ENABLED=false
HORIZON_DASHBOARD_ENABLED=false
```

Do not hardcode `APP_URL` before the first successful deployment. Laravel Cloud
associates the generated environment domain. If generated links are incorrect
after deployment, set `APP_URL` to the exact generated HTTPS URL and redeploy.

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

Configure these exact build commands in this order:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund
npm run build
php artisan optimize
```

`php artisan optimize` creates the release caches in the build phase. It must not
make a network call or depend on an OpenAI key.

Use only the database migration as the deploy command:

```bash
php artisan migrate --force
```

Do not add any of these to the deploy commands:

- `php artisan queue:restart`: no worker exists in the foundation stage, and
  managed queue lifecycle belongs to Cloud;
- `php artisan horizon:terminate`: Horizon is not the Laravel Cloud worker;
- `php artisan optimize:clear`: it removes the optimized artifacts just built;
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

The current `analysis-artifacts` adapter writes persistent user-submitted raw
artifacts to encrypted local storage for a bounded queue handoff. This is a
deployment blocker for imports and asynchronous analysis on Cloud. Keep
`IMPORTS_ENABLED=false` until a reviewed private object-storage adapter is
configured and tested across web and worker processes.

With that capability disabled, `FILESYSTEM_DISK=local` is temporarily acceptable
for pre-alpha framework caches, compiled views, logs routed to stderr, and local
evaluation output. The application has no public upload workflow, and local
files must never be treated as permanent storage. The local disk's HTTP
serve/upload routes are disabled.

## 6. First deployment and verification

1. Review the exact commit and run the complete local/CI quality gate.
2. Confirm the Cloud environment is in lockdown and contains no live provider,
   GGG, payment, or mail credential.
3. Deploy from the selected commit through the Laravel Cloud dashboard.
4. Record the generated domain. Set `APP_URL` only if generated links are wrong;
   set an anchored `TRUSTED_HOSTS` value when the exact host is known, then
   redeploy if either value changes.
5. Open `GET /` and confirm the pre-alpha landing page and exact GGG
   non-affiliation notice render.
6. Confirm `GET /up` returns plain-text `OK` without a database, Valkey, OpenAI,
   GGG, or other external call. Use `/up` as the initial Cloud health probe.
7. Send `GET /ready` with `X-Lootwright-Readiness-Token`. Under the initial
   database/database/sync profile, the response lists and checks only the
   database. Redis appears only when an active cache, session, or queue driver
   uses Redis. Never put the token in a URL or expose it publicly.
8. Run `php artisan migrate:status` from the Cloud command interface and confirm
   every migration is applied.
9. Inspect the deployment logs for build, optimization, boot, and migration
   errors. Do not copy request credentials, tokens, or user content into issues.
10. Confirm imports, rulesets, external links, OpenAI egress, funding, and Horizon
   UI remain unavailable.

No custom domain is required: the generated HTTPS Cloud domain is sufficient
for this entire stage.

Before deployment, `composer run cloud:preflight` safely runs Composer
validation, documentation validation, formatting, static analysis, backend
tests, frontend lint/type/tests, and a production frontend build. It does not
run migrations or modify production data. Dependency advisory checks remain
separate required quality-gate commands.

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
- If an initial PostgreSQL migration fails, inspect `php artisan migrate:status`
  before retrying. A failed transactional migration should remain `Pending`;
  deploy the reviewed fix and let `php artisan migrate --force` retry it. Do not
  manually create constraints or run `migrate:fresh` against the Cloud database.
  If the migration is marked applied or orphaned tables remain, stop and inspect
  the schema before another deployment.

## 9. Cost control

For membership, configure an SMTP-compatible mail provider through Cloud
secrets (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
`MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`). Run
with `AUTH_REQUIRE_VERIFIED_EMAIL=true` in production. Run
`php artisan migrate --force`, then promote an already registered and verified
operator with `php artisan lootwright:admin:promote user@example.com --force`.
Database sessions/cache/queues are supported; no Redis or Horizon worker is
required solely for authentication, the wizard, catalog reads, or admin pages.
The scheduler remains necessary only for the documented pruning/source tasks.
Rollback one batch with `php artisan migrate:rollback --step=1 --force` after a
database backup and maintenance-mode activation.

An optional scheduled task may run `php artisan lootwright:sources:sync-poe-ninja`
every 30 minutes after the source has reviewed policy evidence, a configured
operator contact, and `POE_NINJA_ENABLED=true`. It uses an atomic cache lock;
do not provision a worker, Horizon, or Valkey solely for this task.

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
