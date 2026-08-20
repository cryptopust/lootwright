# ADR 0019: Edition-scoped canonical game data

- Status: Accepted
- Date: 2026-08-20
- Extends: ADR 0005, ADR 0017, ADR 0018

## Context

The governed lifecycle already stores immutable source snapshots, published
rulesets, source links, atomic activation pointers, and append-only activation
history. Production analysis still needs queryable canonical entities without
treating fixture catalogs or an unapproved second-game dataset as authority.
PoE1 and PoE2 may reuse display names and upstream-local identifiers, so neither
a display name nor a global slug is a safe identity.

## Decision

Canonical data is stored under the existing immutable ruleset lifecycle. Its
identity is `(game_edition, ruleset_version_id, entity_type, external_id)`.
Every row cites a same-edition immutable source snapshot and stores a canonical
payload checksum. Character classes and Ascendancies are separate entities;
their relationship is an edition- and ruleset-scoped foreign key. Keystone rows
reference their corresponding passive-node rows. Composite foreign keys prevent
cross-edition ruleset, source, and parent references.

The framework-independent entity contracts are `CharacterClass`, `Ascendancy`,
`PassiveNode`, `Keystone`, `SkillGem`, `SupportGem`, `ItemBase`, `UniqueItem`,
`ModifierDefinition`, `StatDefinition`, and `ContentGoalDefinition`. Contracts
may exist before approved data does. Missing authoritative data remains
unavailable; it is not filled from UI fixtures, AI, names, or the other game.

Each published ruleset receives an immutable dataset approval record with a
classification (`approved_import`, `fixture`, or `unavailable`), provenance
status, and compatibility status. Activation requires exactly
`approved_import + approved + compatible` in the same transaction as the
activation-pointer update. Existing rulesets have no approval record and remain
unavailable until the same governed publication path verifies their checksum,
source links, and approval metadata; migration alone never promotes them.

`ActiveRulesetResolver` never falls back to the newest ruleset. It returns an
explicit status for an unsupported patch, incompatible parser, invalid
provenance, fixture, or unavailable edition. `RulesetRepository` retains
historical lookup. `GameDataRepository` requires both edition and ruleset ID on
every read.

The approved GGG PoE1 passive-tree importer is currently the only canonical
producer. It emits only facts present in its reviewed normalized snapshot:
classes, Ascendancies, passive nodes, and keystones. It does not infer skills,
supports, items, modifiers, stat definitions, content goals, or PoE2 facts.
Icon paths remain inert references; images are not downloaded.

## Consequences

- Historical analysis remains reproducible after activation moves.
- Duplicate publication is idempotent only when metadata, approval, sources,
  and canonical entity checksums match exactly.
- Fixture and legacy data can be inspected and tested but cannot become runtime
  authority.
- Admins can inspect status, provenance, checksums, sources, failures, and
  entity counts; there is no arbitrary canonical-data editor.
- PoE2 remains isolated and unavailable until its own source approval,
  importer, dataset, ruleset, engine, and release ADR exist.
- PostgreSQL migration verification remains mandatory; SQLite is fast feedback,
  not compatibility proof.
