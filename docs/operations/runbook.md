# Production Operations Runbook

Status: hardening baseline, reviewed 2026-08-21. This runbook does not authorize
deployment or source activation. Follow the [deployment guide](deployment.md),
[incident response procedure](../security/incident-response.md), and the exact
[source register](../compliance/source-register.md).

## Operating boundaries

- Public release scope is PoE1. PoE2 code is dormant and public requests must be
  rejected.
- Start with AI, all third-party sources, external links, and outbound networking
  disabled. Enabling a switch never overrides the Policy and Provenance Gate.
- Never run imports in a web request. Use an operator command or the dedicated
  `source-imports` queue.
- Never place share codes, item text, prompts, credentials, cookies, session IDs,
  full IPs, connection strings, or source payloads in tickets or logs.
- Do not run destructive migration commands against production. Migrations use a
  separate operation and role; web and queue processes do not migrate on startup.

## Pre-deployment gate

From a clean checkout at the exact release SHA, run the commands in the
[test matrix](../testing/test-matrix.md). CI must execute the real PostgreSQL
migration/constraint test with `POSTGRES_MIGRATION_INTEGRATION=true`. Confirm:

1. `git diff --check` is clean and no secret or imported data is present.
2. The production image is addressed by digest and runs as UID/GID `10001`.
3. `deploy:check-config` passes with exact trusted hosts/proxies, TLS-verifying
   PostgreSQL/Valkey settings, secure encrypted sessions, and lockdown switches.
4. Mail delivery, queue worker, scheduler, backups, restore isolation, and the
   first super-admin have been exercised in staging.
5. `INERTIA_SSR_ENABLED=false` unless a separately reviewed local SSR process is
   deployed. The current production baseline does not require SSR.

## Deploy and migrate

1. Record release SHA, image digest, operator, start time, and approved change.
2. Put only the migrator on the new image and run:

   ```text
   php artisan migrate --force --no-interaction
   php artisan migrate:status --no-ansi
   ```

3. Start web, queue, and scheduler roles from the immutable image.
4. Check `/up`, then call `/ready` with the token from the secret store.
5. Submit the original synthetic PoE1 smoke fixture. Confirm queued → processing
   → completed, immutable hashes, findings provenance, and manual recipe output.
6. Confirm logs contain the response `X-Correlation-ID`, analysis ID, `poe1`,
   ruleset checksum, engine version, and stage—but no raw fixture.

Rollback deploys the previous compatible image against the expanded schema.
Never automatically run destructive `down()` methods in production. If a schema
rollback is explicitly approved, first verify backups and target paths, stop
writers, and follow the migration-specific recovery note.

## Queue operations

Run workers with explicit queue isolation. Analysis jobs have three attempts and
backoff `[10, 30, 90]`; only typed transient database/cache/workflow failures are
rethrown. Source imports are unique for 15 minutes, locked for 15 minutes, fail at
600 seconds, and use one queue attempt because each adapter owns its bounded HTTP
retry policy.

For a failure:

1. Search structured logs by correlation ID, then analysis ID or source code.
2. Inspect job/outbox state and the coarse failure code. Do not dump payloads.
3. Verify edition, ruleset checksum, engine version, attempts, timestamps, and
   emergency switches.
4. Retry only a typed transient failure. Terminal policy, schema, checksum,
   edition, validation, or provenance failures require correction or a new
   immutable snapshot.
5. Duplicate jobs are safe no-ops; never mutate immutable results to repair one.

## Source import incident

Immediately set `OUTBOUND_NETWORK_ENABLED=false` and the source-specific switch
to false; activate the relevant persisted kill switch if database access is safe.
Do not delete a snapshot or mutate a published ruleset. Quarantine invalid data,
retain the import report/checksums, and activate a previously approved immutable
ruleset only when rollback is authorized. See the
[GGG passive tree procedure](poe1-passive-tree-import.md) and
[external source synchronization](external-source-sync.md).

## AI incident or budget exhaustion

Set `OPENAI_ENABLED=false`, `OPENAI_INTENT_ENABLED=false`,
`OPENAI_EXPLANATIONS_ENABLED=false`, and `OUTBOUND_NETWORK_ENABLED=false` as
needed. Deterministic analysis, upgrade planning, and manual recipes continue.
Never copy full prompts/responses into incident records. Use aggregate telemetry,
hashed request identity, provider status, model, latency, token counts, validation
outcome, and correlation ID.

## Security, privacy, backup, and restore

Follow [incident response](../security/incident-response.md). Preserve immutable
audit records and safe structured logs, rotate affected credentials through the
secret store, invalidate sessions when required, and apply retention/deletion.
Never recover `POESESSID`; the application must never collect it.

Use the guarded isolated restore script and a dedicated `_restore_verify` target.
Keep egress disabled, apply forward migrations, replay deletions/retention, and
verify counts/checksums before promotion. SQLite is not PostgreSQL evidence.
Quarterly production-provider restore evidence remains a release blocker.
