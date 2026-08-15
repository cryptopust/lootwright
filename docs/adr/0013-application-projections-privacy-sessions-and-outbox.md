# ADR 0013: Application Projections, Privacy Sessions, and Transactional Outbox

- Status: Accepted
- Date: 2026-08-15

## Context

ADR 0011 established the owner-scoped analysis workflow and immutable encrypted
snapshots. The complete application lifecycle also needs queryable findings,
recommendations, recipes, provenance, build-level deletion, anonymous use
without tracking identifiers, and durable Redis dispatch. Storing every result
as one mutable JSON document would weaken indexing and deletion boundaries.
Dispatching to Redis only after a PostgreSQL commit would leave a crash window;
dispatching before commit could expose work whose state later rolls back.

## Decision

- Treat authenticated user IDs and anonymous privacy-session IDs as application
  principals. Anonymous credentials are random `<uuidv7>.<256-bit secret>`
  values. PostgreSQL stores only the secret's SHA-256 hash, status, expiry, and
  lifecycle timestamps; it stores no IP address or user agent for identity.
- Persist import attempts, normalized build snapshots, builds, analyses,
  findings, recommendations, recipes, provenance references, policy decisions,
  and optional explanations as separate PostgreSQL projections. The projections
  reference immutable ruleset/source identities and never copy mutable rule
  packages.
- Encrypt normalized facts and deterministic product payloads. Store only
  bounded codes, order, severity/priority, hashes, and edition-scoped selection
  metadata in searchable columns. Verify encrypted payload hashes on export.
- Keep wall-clock lifecycle timestamps outside canonical deterministic payloads.
  Portable export schema `1.0.0` contains hash-verified analysis input/output,
  products, selection, ruleset, provenance, and policy state but no retention or
  processing timestamps.
- Use `analyses.lock_version`, terminal-state checks, and unique product keys as
  optimistic concurrency protection. Duplicate job execution is a no-op before
  work or a typed state conflict at immutable commit; a partial product insert
  rolls back the entire completion transaction.
- Use a narrow transactional outbox only for `build.parse` and `analysis.run`.
  The outbox row is committed with the state that needs the job, then published
  to the isolated Horizon queue after commit. A minute scheduler recovers
  pending rows. Publishers lock each row, retry a transient queue failure at
  most five times with bounded backoff, and jobs retain their own three-attempt
  transient-only policy. This is not an event log or event sourcing.
- Carry game edition and the selected ruleset checksum, when present, in the
  outbox and job payload. A worker revalidates both against persisted immutable
  parameters before doing work. Stale selection is terminal.
- Delete a build by cascading its artifact, normalized snapshot, analyses, and
  products. Full-principal deletion also deletes linked AI metadata through a
  typed application port and invalidates an anonymous privacy credential. Keep
  only unlinkable aggregate deletion counts required for operations.

## Consequences

- Domain and application contracts remain independent of Eloquent, Laravel
  HTTP, Redis, object storage, and provider SDK types.
- Redis is never the sole record that work is required, while duplicate
  publication remains safe because consumers claim persisted state atomically.
- Anonymous analysis is possible without creating an account or storing a
  stable network/device identifier, but possession of the privacy credential is
  the authorization boundary and the credential cannot be recovered.
- The extra relational projections and encrypted payload copies cost storage,
  but allow owner-scoped authorization, deterministic export, targeted cascade
  deletion, provenance visibility, and bounded operational indexes.
- Production analysis remains fail-closed until an approved PoE1 ruleset and
  deterministic analyzer are implemented; this ADR does not activate a game
  dataset or external connector.
