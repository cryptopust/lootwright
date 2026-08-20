# ADR 0003: PoE1-first Delivery

- Status: Superseded in part by ADR 0016 on 2026-08-20
- Date: 2026-08-14

## Context

Path of Exile 1 and Path of Exile 2 share branding and some vocabulary but differ in formats, rules, identifiers, mechanics, and release maturity. Implementing both at once risks a shallow generic ARPG model and cross-game errors.

## Decision

The original decision was to deliver a complete PoE1 MVP first. ADR 0016 now
activates game-scoped PoE1 and PoE2 factual catalogs, intake, wizard selection,
and persistence. The prohibition on speculative PoE2 rules or mappings remains:
ruleset-backed analysis requires separately approved provenance and tests.

Every relevant value, artifact, cache key, job, and record carries a non-null game identity. Adapters, rulesets, identifiers, fixtures, and tests are isolated.

## Consequences

- Product effort focuses on one correct vertical slice.
- Shared abstractions emerge from evidence rather than superficial name similarity.
- PoE2 support requires its own parser/ruleset approval, tests, UI, and activation ADR.
- Users cannot submit PoE2 data to the MVP and receive an approximate PoE1 result.
