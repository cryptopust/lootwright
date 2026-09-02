# Production queue runtime

Lootwright production uses Laravel's PostgreSQL-backed `database` queue. The
authoritative queue table is `public.jobs`; failed jobs are stored in
`public.failed_jobs`.

| Work | Connection | Queue |
| --- | --- | --- |
| Build parsing | `database` | `build-parsing` |
| Deterministic PoE1 analysis | `database` | `deterministic-analysis` |

Jobs are dispatched after the durable workflow/outbox write. Queue connections
use `after_commit=true`, so a job is visible only after its transaction commits.
The outbox scheduler is a recovery path; an already-dispatched row is not
duplicated.

`RunDeterministicAnalysisJob` and `ParseBuildArtifactJob` carry only scalar
identities (UUID, edition enum value, and ruleset checksum). The worker
rehydrates the analysis and the immutable ruleset from PostgreSQL. A complete
`GameRuleset`, repository, service, closure, or provider must never be placed in
a queue payload.

## Cloud worker configuration

Laravel Cloud background processes must run bounded workers with the exact
connection and queue names above. The production analysis worker command is:

```text
php artisan queue:work database --queue=deterministic-analysis --tries=3 --backoff=30 --sleep=3 --rest=0 --timeout=300 --quiet
```

The parsing worker uses the same command with `build-parsing`. After changing a
worker definition, verify it with `cloud background-process:list`, then dispatch
a disposable operator probe or a QA workflow and observe that the queue depth
returns to zero without running a manual worker.

Production runs one process per queue:

- parsing: `database` / `build-parsing`, one process;
- analysis: `database` / `deterministic-analysis`, one process.

Cloud restarts these background processes with the environment deployment. A
post-deployment probe is required because a recorded process definition alone
does not prove that its worker is consuming jobs.

## Operator probe

The operator-only command stores an opaque UUID and timestamps in the configured
cache for one hour. It carries no user or build data and has no HTTP route.

```text
php artisan lootwright:queue:probe dispatch --queue=build-parsing
php artisan lootwright:queue:probe status <probe-uuid>
php artisan lootwright:queue:probe dispatch --queue=deterministic-analysis
php artisan lootwright:queue:probe status <probe-uuid>
```

Do not run `queue:work` during this test. A passing state contains `queued_at`,
`started_at`, and `completed_at`. Repeat after an idle interval and after every
worker-affecting deployment. Probe jobs have one attempt, a 15-second timeout,
and no retry because they are diagnostic markers rather than product work.

## Safe verification

Use `php artisan config:show queue --no-ansi` to inspect effective runtime
configuration without printing secrets. Use `php artisan db:table jobs --no-ansi`
to confirm the schema. `queue:monitor deterministic-analysis --max=1` and
`queue:failed --no-ansi` provide bounded health/failure checks. Never print raw
payloads, share codes, item text, encrypted values, or application secrets.

Product jobs retain their class-level three attempts, 10/30/90 second backoff,
300-second timeout, terminal workflow state, and idempotent repository state
transitions. Worker restarts are safe because product payloads contain only
stable scalar identities and authoritative state is rehydrated from PostgreSQL.
