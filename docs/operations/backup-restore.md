# PostgreSQL Backup and Tested Restore Runbook

Status: provider-neutral procedure. A production provider, jurisdiction, RPO/RTO,
named operators, encryption-key custody, and first successful quarterly exercise must
be recorded before public release.

## Backup baseline

PostgreSQL is authoritative; Redis is not restored as a source of truth. Configure
encrypted full backups plus provider-supported point-in-time recovery where available.
Backups use a separate identity/key, immutable access logs, private storage, daily
integrity checks, and at most 30-day MVP retention. Backup operators may not use the
web runtime database role.

Do not back up Redis, the shared `lootwright-artifacts` volume, raw queue handoff
objects, application logs, cache/config files, or container filesystems. Raw artifacts
have a one-hour encrypted handoff ceiling and must be pruned, not preserved in a backup.

For a self-managed exercise, use a custom-format logical backup with a credential
supplied through a protected PostgreSQL service file or secret manager, never a
password argument:

```text
pg_dump --format=custom --no-owner --no-privileges --file=lootwright-COMMIT.backup DATABASE
pg_restore --list lootwright-COMMIT.backup
```

Encrypt before the bytes leave the isolated backup host. Do not retain a plaintext
working copy, include it in an image, commit it, upload it to CI artifacts, or place a
filename containing user identity in storage. Record UTC time, PostgreSQL version,
source release/migration, encrypted object checksum, backup-tool result, retention
expiry, and operator. Evidence contains no decrypted rows.

## Quarterly restore exercise

1. Approve a maintenance record with exercise owner, backup ID/checksum, RPO/RTO,
   isolated target, no-egress proof, current release image, and deletion-reconciliation
   owner.
2. Create a fresh isolated database whose name ends in `_restore_verify`. It must not
   accept application/worker traffic and must have no route to OpenAI, GGG, mail,
   analytics, or the public Internet.
3. Place a decrypted working copy on encrypted ephemeral storage. Verify checksum and
   run `pg_restore --list` before changing the target.
4. From the reviewed source tree, invoke
   `scripts/operations/verify-restore.sh`. It refuses without
   `ALLOW_ISOLATED_RESTORE=yes`, a regular backup file, an isolated target identity, an application
   decryption key supplied through the exercise secret manager, a protected
   PostgreSQL service-file entry, separate Laravel connection variables, and the
   required database-name suffix. Passwords remain in the secret environment/service
   file and never appear in process arguments.
5. The script cleans only the verified isolated target, restores without owners or
   privileges, applies forward migrations, and runs application, AI, artifact, import,
   and deleted-session retention pruning. Never point it at production.
6. Using read-only aggregate queries, compare table/migration counts and sampled
   ciphertext/canonical hashes against protected backup evidence. Do not print or
   screenshot decrypted PoB, notes, prompts, emails, session material, or analysis
   payloads.
7. Verify owner authorization and deletion tombstones/current deletion records before
   any read access. Confirm expired artifacts and linkable deleted-user data are absent.
8. Run application smoke probes with AI, egress, funding, external links, rulesets, and
   imports off. Redis starts empty; republish only durable pending outbox rows after the
   exercise explicitly tests that path.
9. Record achieved RPO/RTO, counts/hashes, pruning results, failures, owners, and
   follow-up actions. Destroy the isolated database and plaintext working copy using
   the provider-approved process after evidence review.

## Recovery use

A real recovery follows incident command. Restore to isolation first, apply current
deletion/pruning, verify integrity and least-privilege roles, rotate exposed secrets,
and activate one capability at a time. AI and all egress are last. Never declare user
deletion complete merely because the live database is clean while retained backups
remain inside their approved expiry window.

If any checksum, migration, deletion, authorization, or provenance check fails, keep
the target isolated and treat the exercise as failed. Do not silently recompute or
repair deterministic snapshots.
