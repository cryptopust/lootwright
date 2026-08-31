# PoE1 live vertical-slice audit

## Scope

The production slice is deliberately narrow: a PoB XML submitted by a user is
parsed by the independent PoE1 adapter, normalized into canonical build facts,
resolved against an immutable approved GGG passive-tree ruleset, analyzed by
the deterministic engine, passed through the PoE1 upgrade planner, and rendered
as manual Trade recipes. No AI, live prices, Trade API calls, or PoE2 behavior
is part of this gate.

## Evidence

- Parser: `Pob1Parser`/`Pob1Normalizer`, parser version `1.0.0`.
- Engine: `Poe1DeterministicAnalysisEngine` (`1.0.0`) through
  `ProductionPoe1DeterministicAnalysisEngine`.
- Ruleset source: official GGG skilltree export, revision
  `8bd138b32ea2631455cac5935bfab089f826094f`, source SHA-256
  `7e9f755e33152129ebf36c2ebdad639c527e4ad70d274b1fefb860f30ca01122`.
- Runtime marker: `PRODUCTION_CANONICAL`; `TEST_FIXTURE` is rejected by the
  live acceptance command.
- Acceptance command: `php artisan lootwright:acceptance:poe1`.

## Production blocker classification

The repository previously failed closed when no approved active ruleset was
present. That behavior remains intentional and is now exercised by the
canonical-only acceptance command. Fixture import/analyzer classes are test
utilities and are rejected from production paths; occurrences of “fixture” in
those test utilities are not production blockers.

The remaining activation dependency is operational: an operator must import
and validate the approved GGG snapshot and activate its immutable ruleset in
Laravel Cloud. No destructive migration or production data reset is required.
