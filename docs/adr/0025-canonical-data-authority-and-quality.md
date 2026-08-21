# ADR 0025: Edition-scoped normalization, source authority, and data quality

- Status: Accepted
- Date: 2026-08-21
- Extends: ADR 0017, ADR 0019, ADR 0020

## Context

Immutable snapshots and rulesets establish provenance but do not decide how
different source schemas become canonical entities, how category-specific
authority is assigned, or how disagreement and missing coverage are exposed.
Choosing the first reachable source would silently convert accessibility into
permission and could mix PoE1 and PoE2 facts.

## Decision

- Use distinct `Poe1GameDataNormalizer` and `Poe2GameDataNormalizer`
  implementations behind a shared application port. Each accepts only its own
  schema ID and edition, an approved immutable snapshot identity, and bounded
  canonical envelopes. Unknown envelope fields, unsafe nesting, contradictory
  duplicates, and cross-edition attributes fail closed.
- Extend canonical entity types for tags, requirements, damage/ailment and
  offence/defence/reservation mechanics, attributes, jewels, clusters,
  edition-specific mechanics, and Trade vocabulary. These contracts do not
  imply data availability.
- Configure authority per game and data category in
  `game_data_source_authorities`. Absence or `enabled=false` means no authority.
  Only the reviewed official PoE1 passive-tree source is seeded, and only for
  classes, Ascendancies, passive nodes, and keystones.
- Rank agreeing candidates using category-specific precedence from
  `config/game-data.php`: official structured, approved upstream, trusted
  community, derived, then heuristic. Compare canonical fact checksums without
  provenance. If facts differ, append a `canonical_data_conflicts` quarantine
  record and select neither candidate, regardless of rank.
- Report coverage from the exact active ruleset. Verified expected counts may
  come only from approved ruleset quality metadata. Without a denominator,
  completeness remains unknown. Fixtures and presentation catalogs never
  contribute production coverage.
- Record checksum-based update outcomes in immutable
  `source_update_observations`. The queued, fixed-adapter import lifecycle may
  stage a changed snapshot, but never activates a ruleset automatically.

## Consequences

Source precedence is deterministic but cannot conceal conflicts. Both editions
have normalization contracts without sharing schemas or facts. The clean
production coverage is honestly zero until an operator imports and explicitly
activates approved data. Broader PoE1 analysis and all PoE2 canonical analysis
remain blocked on source-specific permission, schema, validation, and release
review.
