# ADR 0011: Application Workflow and Analysis Persistence

- Status: Accepted
- Date: 2026-08-14

## Context

Lootwright needs an owner-scoped workflow around the framework-independent
deterministic ports before approved rulesets and production analyzers exist.
Parsing and analysis are bounded background tasks, HTTP retries can be
concurrent, and user deletion must cover both short-lived artifacts and
immutable analysis versions without putting Laravel concerns in `src/Domain`.

The earlier format-only import path kept raw PoB input in memory because it ran
synchronously. A queued workflow cannot rely on request memory. It therefore
needs a narrowly scoped, recoverable handoff while continuing to minimize raw
input retention.

## Decision

- Put provider-neutral commands, DTOs, workflow states, use cases, and ports in
  `src/Application/Workflow`. They may depend inward on domain contracts but
  import no Laravel, database, queue, filesystem, HTTP, or provider type.
- Put PostgreSQL repositories, encrypted local-object-storage adapters,
  Policy Gate adapters, Laravel events, and Horizon jobs in
  `app/Modules/Analysis`.
- Store only an encrypted raw artifact in private object storage for the queue
  handoff. PostgreSQL stores its opaque key, byte count, hashes, game, locale,
  state, and expiry. Delete the raw object immediately after a terminal parse
  outcome and, in every case, within the configured one-hour ceiling through
  hourly pruning.
- Store normalized inputs, parameters, and deterministic outputs as encrypted
  immutable canonical snapshots with independent SHA-256 hashes. Do not place
  large raw artifacts in database JSON columns or queue payloads.
- Hash owner and idempotency identities with purpose-separated application-key
  HMACs. A unique owner-scoped idempotency hash is the concurrency arbiter;
  replay requires the same artifact, parameters, and game.
- Expose `queued`, `processing`, `clarification_required`, `completed`,
  `failed`, and `policy_blocked`. Jobs claim state atomically and do nothing
  when replayed after a terminal transition.
- Retry only typed transient failures, with bounded Horizon attempts and
  backoff. Invalid input, game mismatch, snapshot mismatch, and policy denial
  are terminal.
- Resolve an exact adapter and immutable ruleset before deterministic work.
  Run the Policy and Provenance Gate after resolution and before both
  deterministic derivation and manual external-action recipe generation.
- Keep the production analysis engine fail-closed until the ruleset catalog
  and an approved deterministic PoE1 analyzer are implemented. Tests may bind
  deterministic fakes; no fixture fact becomes production game data.
- Delete owner-scoped artifacts, analyses, and earlier retained imports through
  typed module ports in one database transaction. Keep only an unlinkable
  operational tombstone containing aggregate deletion counts and time.
- Use Laravel events only as after-commit notifications. PostgreSQL remains the
  source of truth; event sourcing is not introduced.

## Consequences

- HTTP controllers and Horizon jobs remain thin delivery adapters while the
  workflow is testable without Laravel types.
- A worker crash, duplicate delivery, or concurrent submission cannot create a
  second logical analysis for the same idempotency key.
- Raw queue handoff data exists briefly at rest, so encryption, expiry pruning,
  storage isolation, backup exclusion, and deletion monitoring are mandatory.
- Real analysis remains unavailable rather than silently selecting a latest
  ruleset or inventing facts. Prompt 04 and Prompt 07 remain policy/provenance
  prerequisites.
