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

## Safe verification

Use `php artisan config:show queue --no-ansi` to inspect effective runtime
configuration without printing secrets. Use `php artisan db:table jobs --no-ansi`
to confirm the schema. `queue:monitor deterministic-analysis --max=1` and
`queue:failed --no-ansi` provide bounded health/failure checks. Never print raw
payloads, share codes, item text, encrypted values, or application secrets.
