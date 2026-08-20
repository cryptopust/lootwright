# ADR 0017: Governed game-data snapshots and ruleset lifecycle

Date: 2026-08-20

Status: accepted

## Context

Lootwright already has a deny-by-default Policy and Provenance Gate and a
domain-level `RulesetIdentity`, but it had no durable import, immutable source
snapshot, conflict quarantine, ruleset publication, or activation lifecycle.
The production analyzer must not be activated from mutable fixtures, live
network state, an unreviewed dataset, or an ambiguous "latest" rule version.

The active release remains PoE1 only. Dormant PoE2 types remain isolated and
the schema always carries `game_edition`; this ADR does not approve a PoE2
source or public behavior.

## Decision

- Extend `policy_data_sources` as the one source-definition registry. Do not
  introduce a second registry. Canonical lifecycle IDs are
  `USER-POB-001`, `USER-ITEM-TEXT-001`, `GGG-POE1-SKILLTREE-001`,
  `GGG-POE1-ATLASTREE-001`, `POEWIKI-CARGO-001`,
  `POENINJA-ECONOMY-001`, and `REPOE-CANDIDATE`.
- Reuse `external_source_sync_runs` for every operator/import attempt. A successful
  normalized import creates one content-addressed `source_snapshots` row.
  The unique `(source_code, game_edition, checksum_sha256)` identity makes a
  repeated checksum an idempotent replay, not a second snapshot. A separate
  `upstream_checksum_sha256` preserves raw-source identity when normalization
  produces a different canonical checksum.
- Store bounded canonical normalized JSON only. Do not preserve an unbounded
  raw response. Every snapshot includes source/version, game, exact URL,
  upstream revision, retrieval time, SHA-256, content type, license identifier,
  status, and schema version.
- If the same source/game/upstream revision arrives with a different checksum,
  create a `source_conflicts` quarantine record and retain neither candidate
  payload nor authority to publish it.
- Publish canonical content as `ruleset_versions` linked to every contributing
  snapshot. Publication verifies payload checksum, source existence, source
  game, snapshot status, and source governance. Published rows and their source
  links are protected from update/delete by database triggers. Corrections
  create a new version and may link to the superseded version.
- Resolve active rules through one row per exact game/patch/league/parser scope
  in `ruleset_activations`. Activation locks the ruleset and current scope,
  validates every source through the central Policy Gate and configuration,
  changes the pointer, and appends `ruleset_activation_history` in one database
  transaction. Rollback means activating a previously published version; it
  never mutates history.
- Bind the existing `RulesetResolver` port to a database resolver that requires
  an exact game, patch, nullable league, and parser-version activation scope.
  It never falls back to a latest or adjacent version and performs no network
  work during request-time resolution.
- `POEWIKI_IMPORT_ENABLED`, `POENINJA_ECONOMY_ENABLED`, and
  `OPENAI_EXPLANATIONS_ENABLED` default to false. The existing adapter switch
  and the new governance switch must both permit a Wiki/poe.ninja adapter.
  Environment configuration can only further restrict Policy Gate authority.
- User inputs may be normalized privately but are explicitly denied as
  ruleset authority. Atlas data is allowed in principle but outside MVP.
  poe.ninja is market context, never ruleset authority. Wiki is review-gated.
  RePoE is prohibited and disabled under the current decision.
- The first concrete official adapter is the operator-only PoE1 passive-tree
  importer. It accepts only the reviewed root `data.json` at commit
  `8bd138b32ea2631455cac5935bfab089f826094f`, verifies raw checksum
  `7e9f755e33152129ebf36c2ebdad639c527e4ad70d274b1fefb860f30ca01122`,
  and defaults off behind `GGG_PASSIVE_TREE_IMPORT_ENABLED`. URL mode is an
  exact commit-pinned raw GitHub allowlist; arbitrary branches and URLs remain
  denied. A schema failure stores only immutable rejection metadata and a
  quarantine record, never the raw body.

## PostgreSQL and portability

Lifecycle identities use UUIDv7 strings, matching the repository's workflow
standard. References to `policy_data_source_versions.id` are unsigned bigint;
source-code references are 64-character strings; all other lifecycle foreign
keys are UUID. Composite source/version foreign keys prevent a source code from
being paired with another source's numeric version ID.

PostgreSQL adds enum-like checks, SHA-256 format checks, league-scope
consistency, 2 MiB JSON payload limits, and immutable-row triggers. SQLite keeps
fast behavior tests and equivalent immutable triggers, but it is not accepted
as proof of PostgreSQL compatibility. Release evidence requires disposable
PostgreSQL fresh migration, rollback, and reapply.

## Consequences

- A source adapter cannot become executable merely because code or a feature
  flag exists. Exact policy evidence, operation, conditions, and source switch
  remain required.
- An analyzer can eventually resolve a stable active pointer without network
  access during a user request.
- Publication and activation add operational ceremony, but a bad version is
  reversible by pointer activation without rewriting analyzed history.
- This lifecycle does not provide or activate a production analyzer. The
  `UnavailableDeterministicAnalysisEngine` binding stays in place until later
  release gates are met.
