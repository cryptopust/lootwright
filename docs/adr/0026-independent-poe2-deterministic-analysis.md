# ADR 0026: Independent PoE2 deterministic analysis

- Status: Accepted (gate-controlled)
- Date: 2026-08-27

## Context

PoE2 requires a separate ruleset, identifiers, parser contract, and mechanics.
The existing application previously exposed only a dormant PoE2 parser and an
unavailable analysis result. Enabling PoE2 by copying PoE1 rules would create
unsafe mechanical leakage.

## Decision

PoE2 uses its own `Poe2Ruleset`, loader, canonical resolver, passive/skill/
modifier resolvers, analysis manifest, and deterministic engine. The production
workflow routes by edition through `ProductionEditionDeterministicAnalysisEngine`.
Every PoE2 canonical reference is checked against the active PoE2 ruleset and
foreign `poe1` identifiers are rejected. Ruleset identity, publication status,
checksum, parser version, and edition must match at execution time.

The public edition allowlist remains independently gate-controlled. The
reviewed Lootwright-authored PoE2 0.3.0 canonical dataset and manifest are now
approved for the beta release; stale or missing runtime snapshots still fail
closed. PoE1 release status is not blocked by this PoE2 gate.

## Consequences

- PoE2 findings are limited to rules explicitly present in its manifest; absent
  mechanics are reported as unsupported rather than inferred from PoE1.
- PoE2 data, Trade vocabulary, content goals, fixtures, and golden tests remain
  edition-scoped.
- PoE2 0.3.0 release evidence is tracked by the independent golden corpus,
  cross-game negative tests, and the edition-specific staging gate.
