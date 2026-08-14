# ADR 0003: PoE1-first Delivery

- Status: Accepted
- Date: 2026-08-14

## Context

Path of Exile 1 and Path of Exile 2 share branding and some vocabulary but differ in formats, rules, identifiers, mechanics, and release maturity. Implementing both at once risks a shallow generic ARPG model and cross-game errors.

## Decision

Deliver a complete PoE1 MVP first. Define shared ports where they prevent structural lock-in, but keep PoE2 inactive and free of speculative rules or mappings. PoE2 becomes a separately provenanced adapter in phase two only after PoE1 release gates pass.

Every relevant value, artifact, cache key, job, and record carries a non-null game identity. Adapters, rulesets, identifiers, fixtures, and tests are isolated.

## Consequences

- Product effort focuses on one correct vertical slice.
- Shared abstractions emerge from evidence rather than superficial name similarity.
- PoE2 support requires its own parser/ruleset approval, tests, UI, and activation ADR.
- Users cannot submit PoE2 data to the MVP and receive an approximate PoE1 result.

