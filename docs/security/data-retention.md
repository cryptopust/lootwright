# Data Retention and Deletion

Status: application retention baseline. Production backup-provider guarantees,
jurisdiction, controller contact, and legal-hold procedure remain release gates.

## Retention schedule

| Data | Default | Maximum or trigger | Disposal |
| --- | --- | --- | --- |
| Synchronous pasted PoB/item text | Request memory only | End of request | Never persisted |
| Queued raw artifact | Until parse completes | 60 minutes | Encrypted object deleted; hourly retry pruning |
| Consented normalized PoB import | 24 hours | 168 hours | Encrypted row deletion |
| Analysis build/snapshot/products | 30 days | User deletion or configured shorter policy | Artifact root deletion cascades snapshots, analyses, findings, recommendations, recipes, policy/provenance, and explanations |
| Anonymous privacy session | Up to 168 hours | Expiry or deletion | Becomes unusable immediately; tombstone removed after 7 days |
| AI validated response cache | 1 hour | Expiry or user deletion | Cache plus owner index deletion |
| User/IP-linked AI audit and daily counters | 30 days | User deletion where owner-linked | Daily pruning; global unlinkable aggregates may remain |
| Policy decision audit | Operational policy evidence | Approved legal/operational schedule | Contains no raw user input or secret |
| User deletion aggregate | Operational/legal minimum | Approved legal schedule | Unlinkable counts only |
| Application logs | 14 days by default | Incident hold approved by incident commander | Rotation and secure deletion |
| Encrypted PostgreSQL backups | Deployment-defined, maximum 30 days for MVP | Expiry, verified purge, or legal hold | Provider deletion plus restore tombstone replay |

`security:prune-retained-data` runs daily and removes expired analysis roots,
AI audit/cache/index data, user/IP budget counters, and expired/deleted privacy
session tombstones. `analysis:prune-artifacts` and `pob:prune-imports` retain
their hourly schedules. Commands are bounded and safe to rerun.

## Consent and access

Transient format review needs deliberate submission but no storage consent.
Persistence requires an account or valid anonymous privacy session, explicit
storage consent, a retention choice within the maximum, and an owner-scoped
idempotency key. Optional AI additionally needs explicit opt-in and separate
cache permission. Consent does not broaden source or provider policy.

Portable export is owner-scoped, canonical, timestamp-free, hash-verified JSON.
It excludes raw share codes, lifecycle timestamps, secrets, sessions, and
unnecessary personal information.

## Complete deletion

Build deletion removes that build and all dependent analyses/products.
Principal deletion removes all owner-scoped artifacts and analyses, retained PoB
imports, AI audits, user budget counters, cache indexes/entries, analysis
explanations, and the anonymous credential when applicable. Object-storage keys
are deleted before the primary record is finalized. Only unlinkable aggregate
counts remain.

Deletion from the live database is immediate after a successful response.
Encrypted backups age out within the configured backup retention. A restored
backup must remain isolated until deletion tombstones and current retention
pruning have run; only then may application or worker access be enabled. A
quarterly restore exercise must prove this sequence and record counts/hashes,
not user content.

## Legal holds and failures

No legal hold exists by default. A future hold must have written authority,
scope, owner, expiry, access log, and user-notice analysis. It must never be
created through an application feature flag. If object deletion or a database
transaction fails, return a transient failure and retry the bounded operation;
never claim deletion completed while linkable primary data remains.
