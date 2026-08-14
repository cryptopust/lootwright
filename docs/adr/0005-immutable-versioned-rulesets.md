# ADR 0005: Immutable Versioned Rulesets

- Status: Accepted
- Date: 2026-08-14

## Context

Path of Exile rules change by game, patch, and sometimes league. Source data, transformations, and parsers also evolve. A mutable "current" table would make old results irreproducible and source errors hard to audit.

## Decision

Publish immutable content-addressed rulesets identified by:

- game;
- patch;
- league when relevant;
- source ID and source version;
- parser version;
- SHA-256 checksum;
- provenance record;
- publication and effective timestamps.

Corrections create new rulesets linked by supersession. Activation verifies exact game, parser compatibility, provenance approval, and checksum. Unknown patch or league never silently resolves to latest.

## Consequences

- Historical results are reproducible and explainable.
- Imports need review, storage, checksum tooling, and migration discipline.
- Ruleset references become part of result and cache identity.
- Removing a bad ruleset means deactivation for new work, not mutation of history.

