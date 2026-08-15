# Production Packaging and Deployment

Status: deployable single-host/container-orchestrator package implemented on
2026-08-16. No image was published, repository pushed, domain registered,
infrastructure created, or environment deployed by this work.

## Artifact and trust model

`Dockerfile.production` builds one immutable application image. Its frontend and
Composer dependencies come only from `package-lock.json` and `composer.lock`; the
runtime contains production Composer packages and built frontend assets but no
`node_modules`, test suite, private fixtures, or development server. It runs as UID/GID
`10001`, uses a read-only root filesystem in the production Compose definition, drops
Linux capabilities, and enables `no-new-privileges`.

CI builds and inspects the image but does not push it. An authorized release operator
must build from a reviewed commit, scan the resulting image with the deployment's
approved scanner, sign/attest it under the organization's process, and deploy only a
registry reference pinned by `@sha256:` digest. A mutable tag is not a deployment
identity.

The Compose package requires `LOOTWRIGHT_IMAGE_REPOSITORY` and the separate
64-character `LOOTWRIGHT_IMAGE_DIGEST`; it always joins them with `@sha256:` and has no
tag-only fallback.

```powershell
docker build --file Dockerfile.production --build-arg APP_RELEASE_SHA=COMMIT_SHA --tag lootwright:review-COMMIT_SHA .
docker image inspect lootwright:review-COMMIT_SHA
```

Do not push or publish the image without explicit authorization. The build performs
package-registry access only while resolving the committed lock files. Runtime health
checks require no public network or provider credential.

## Process roles

[`compose.production.yaml`](../../deploy/compose.production.yaml) uses the same image
for four exact roles:

| Role | Process | Responsibility |
| --- | --- | --- |
| `web` | Nginx on internal port 8080 plus PHP-FPM | HTTP/Inertia/API delivery and static built assets |
| `queue` | `php artisan horizon` | `default`, `build-parsing`, and `deterministic-analysis` Redis queues |
| `scheduler` | bounded minute loop around `schedule:run` | outbox dispatch, retention pruning, and scheduled Horizon work |
| `migrate` | `migrate --force --isolated` | one-off DDL with the separate migration role; never an automatic web startup action |

The local encrypted raw-artifact handoff is a private named volume shared by web and
workers. This package is therefore a single-host MVP artifact. Multi-host deployment
requires an approved private object-storage adapter with equivalent encryption,
one-hour lifecycle, deletion, and Policy Gate controls before scaling across hosts.

PostgreSQL and Redis are intentionally not created by the production Compose file.
They must already exist on a private network with the controls below. Merely rendering
the Compose configuration does not create an external resource.

## Configuration and preflight

Copy `deploy/env.production.example` to an ignored operator-owned configuration source
or map its non-secret values through the deployment platform. Inject `APP_KEY`,
database/Redis passwords, readiness token, and CA files from a secret manager; never
put them in the copied reference file, image, CI variables printed to logs, or command
arguments.

Start in lockdown mode. The checked reference sets the global Policy Gate switch and
imports, rulesets, links, AI, egress, Horizon UI, and funding to their safe state.

```powershell
php artisan deploy:check-config
```

The command performs no network call and prints only failing variable names. It
requires HTTPS, a release SHA, non-placeholder application/readiness/Redis secrets,
PostgreSQL `verify-full`, Redis TLS peer verification, explicit hosts/proxies, secure
encrypted sessions, stderr logging, AI/egress/funding off, and Horizon UI off. In
lockdown it additionally requires every user-facing capability off and the global
kill switch on.

Leaving `DEPLOYMENT_LOCKDOWN_MODE=true` is appropriate for the initial health-only
rollout. Changing it to false does not itself authorize a capability: every individual
switch, active source/ruleset, Policy Gate decision, release checklist, and operator
record remains required.

## PostgreSQL and Redis

Production PostgreSQL requirements:

- private listener only; `sslmode=verify-full` with the expected CA and hostname;
- a runtime role with LOGIN but NOSUPERUSER, NOCREATEDB, NOCREATEROLE, and
  NOREPLICATION, limited to required DML/schema usage;
- a separate migration role that owns DDL and is injected only into the one-off
  migrator;
- role-level statement, lock, and idle-in-transaction timeouts;
- encrypted, access-audited backups with a maximum 30-day MVP retention.

Production Redis requirements:

- private listener, TLS peer/name verification, dedicated ACL username and random
  password;
- separate databases/prefixes for default/session data, cache, queue, and Horizon
  metadata; queue loss may delay work but never remove authoritative PostgreSQL data;
- memory/eviction limits that do not evict active queue data, plus alerting for queue
  wait, failed jobs, and memory pressure.

The application image contains no PostgreSQL or Redis server. Normal tests use
SQLite/array/sync fakes; CI migration sanity uses an ephemeral PostgreSQL service.

## HTTPS, proxy, and Horizon boundary

The container binds its HTTP port to loopback by default. A separately managed edge
proxy/load balancer terminates TLS, redirects HTTP to HTTPS, preserves the original
host and approved `X-Forwarded-*` values, strips client-supplied forwarding headers,
and connects only over the private/loopback path. `TRUSTED_HOSTS` contains exact host
regexes; `TRUSTED_PROXIES` contains exact proxy IPs/CIDRs. Never use `*` merely to make
URL generation or secure cookies work.

The edge must set HSTS only after HTTPS and rollback behavior are verified. The
application emits the remaining CSP, clickjacking, nosniff, referrer, permissions,
opener, and resource-policy headers.

Horizon's dashboard is denied unless the process is both in `local` and
`HORIZON_DASHBOARD_ENABLED=true`. Production keeps the flag false and exposes no
Horizon route through the edge. Operators use aggregate logs/metrics and
`horizon:status`; they do not expose queue payloads or retry unknown jobs.

## Health and observability

- `GET /up` is public liveness. It returns only `OK`, checks no dependency, and needs
  no AI, GGG, payment, dataset, database, or Redis credential.
- `GET /ready` is a protected operator check. A random `READINESS_TOKEN` header is
  required; it checks only PostgreSQL and Redis and returns `ok`/`failed`, never an
  exception, host, secret, or row.
- Compose web health uses local `/up`; queue health uses `horizon:status`; scheduler
  health reads a local heartbeat less than three minutes old.

Nginx logs request ID, method, path without query text, status, byte count, and
duration. It omits client IP, user agent, referrer, cookies, and request bodies.
Laravel logs go to stderr through the recursive sensitive-data redactor. Monitor
availability, response class/latency, queue depth/wait/failures, scheduler heartbeat,
database/Redis saturation, retention command outcomes, backup age, and Policy Gate
decision counts. Never attach raw imports, item text, prompts, notes, session secrets,
or decrypted database values to telemetry.

## Migration and low-downtime rollout

Migrations do not run during container startup. Use an expand/contract sequence:

1. Back up and verify backup freshness. Put user-facing capabilities in lockdown when
   the change is not backward compatible.
2. Build and verify the new digest. Review every migration for locks, table rewrites,
   defaults, indexes, and PostgreSQL-version behavior.
3. Run the `migrate` profile once with the separate DDL role. Add nullable columns,
   new tables, or concurrent indexes before code depends on them; do not drop/rename
   data used by the previous image.
4. Start new web instances, verify `/up`, protected `/ready`, headers, version, and
   owner-isolated probes, then replace old web instances gradually.
5. Restart Horizon with `php artisan horizon:terminate` so new supervisors claim only
   compatible, versioned jobs. Confirm stale-ruleset and old-payload denial.
6. Start/verify the scheduler once. Re-enable reviewed capabilities one at a time.
7. Remove old columns or compatibility code only in a later release after rollback is
   no longer required and retention/queue windows have passed.

PostgreSQL transactional DDL does not make a breaking schema change safe. Large-table
or concurrent-index migrations require a separately reviewed migration plan and may
not be improvised in the deploy window.

## Rollback

Application rollback selects the previous signed image digest, leaves the expanded
schema in place, terminates Horizon, starts previous workers/web, verifies health and
authorization, and records the reason. Prefer a forward fix. Never automatically run
`migrate:rollback` in production: a down migration may destroy data or be incompatible
with already-processed jobs.

If an irreversible migration was applied, keep capabilities disabled, preserve the
database, and follow incident response. Restore is the last resort and must use the
isolated [backup and restore runbook](backup-restore.md), including deletion and
retention reconciliation before access.

## Operations after release

The scheduler must run the hourly raw-artifact/import pruning, daily application/AI
retention pruning, and minute outbox dispatch. User deletion remains synchronous and
owner scoped; backup copies age out within the approved retention and restored copies
must replay current deletion/pruning state before use.

AI is stopped by `OPENAI_ENABLED=false` and `OUTBOUND_NETWORK_ENABLED=false`, plus the
Policy Gate/global switch and local/monthly budgets. Funding remains code-disabled in
addition to `FUNDING_ENABLED=false`. Incident containment follows the
[incident-response runbook](../security/incident-response.md).
