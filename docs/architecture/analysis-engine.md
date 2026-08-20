# Deterministic Analysis Engine

The deterministic engine is the sole authority for findings and the future
source of upgrade recommendations. AI can select from a closed intent
vocabulary or explain existing products; it cannot participate in canonical
calculation or product creation.

## Contracts and dependency direction

The framework-independent contracts live under `src/`:

- `BuildSnapshot` binds a build to edition, patch, league, parser, catalog, and
  input checksum.
- `CanonicalBuild` accepts only an exact matching `RulesetIdentity`.
- `Finding` binds analysis ID, stable rule code, ruleset reference, evidence,
  trace, observed/expected values, affected entities, provenance, and
  confidence.
- `Recommendation` binds edition, analysis, deterministic findings, impact,
  priority, alternatives, and evidence trace.
- `DeterministicAnalysisEngine` is the application workflow port used by jobs.

Laravel infrastructure resolves encrypted persisted inputs and immutable local
rulesets, then calls a pure game adapter. Pure rules never import Laravel,
database, network, cache, queue, filesystem, wall-clock, randomness, locale, or
AI types.

## Execution sequence

```text
edition-labelled artifact
    -> bounded edition-specific parser
    -> immutable normalized artifact snapshot
    -> exact ruleset resolution
       (edition + patch + league + parser + checksum)
    -> Policy Gate decision for deterministic analysis
    -> pure edition-specific engine
    -> canonical findings in stable rule order
    -> transactional encrypted projections and provenance
    -> optional explanation of exact existing codes
```

The worker rechecks the persisted edition and resolved ruleset checksum before
execution. If activation changes between resolution and execution, the job
fails closed rather than running a different ruleset.

## Determinism requirements

Given the same normalized input, ruleset identity, parser version, and
configuration, output must be byte-stable. Rules therefore:

- run only when their required facts are present;
- use reviewed aliases and exact integer/decimal policies;
- sort findings by a declared stable rule order;
- represent unknown/ambiguous inputs explicitly;
- never substitute zero, “latest,” another edition, a nearby identifier, live
  network state, or AI output;
- cite the rule and input/source evidence used; and
- make changes through a new immutable ruleset/engine version.

Reanalysis creates a child analysis and computes added, resolved, and unchanged
finding-code sets without mutating the parent.

## Current PoE1 engine

Production binds the workflow port to
`ProductionPoe1DeterministicAnalysisEngine`, which accepts only normalized
`pob1` artifacts and resolves an exact local PoE1 ruleset. The pure
`Poe1DeterministicAnalysisEngine` currently evaluates a narrow controlled set:

- missing character level, class, or Ascendancy data quality;
- empty required equipment slots;
- reported fire/cold/lightning resistance below its reported maximum;
- negative reported chaos resistance;
- negative unreserved mana or invalid reported reservation;
- disabled gems;
- insufficient links only when a main active skill is reliably identified;
- duplicate/conflicting equipment-slot data; and
- passive node IDs absent from the active official GGG snapshot.

It deliberately has no arbitrary life, energy-shield, DPS, armour, evasion, or
block thresholds. It does not infer build type. Missing facts produce no
invented finding.

The infrastructure verifies the published ruleset canonical checksum, requires
the reviewed deterministic-analysis manifest, verifies the linked official
passive-tree snapshot checksum, and supplies only the normalized node set and
provenance to the pure engine.

## Current limitations

- Production execution produces findings but returns empty recommendation and
  manual-recipe lists. Existing planner/recipe demonstrations use test or demo
  vocabulary and are not production advice.
- There is no PoE2 deterministic engine, PoE2 ruleset, or PoE2 passive-tree
  source adapter. PoE2 input cannot fall back to PoE1.
- PoB normalization is intentionally structural and does not copy Path of
  Building formulas or bundled data. Unsupported fields remain disclosed.
- Character catalog facts are version-controlled code metadata rather than
  linked governed source snapshots.
- Result UI demo routes remain fixture-oriented; owner-scoped analysis pages
  exist, but the complete production findings/recommendation experience is not
  yet the release-ready product flow.

## AI authority boundary

The explanation request is constructed from existing typed findings and
recommendations. Its strict schema fixes array length and permits only exact
existing codes plus plain explanation text. Extra canonical-fact properties are
rejected. After schema validation, code order and forbidden content are checked
again; any violation falls back to deterministic text. The before/after domain
objects remain unchanged.

## Required tests for every engine adapter

Each game adapter requires independent unit, reduced-fixture, golden,
property/boundary, checksum, missing-data, replay, and cross-edition tests.
Architecture tests must prove both directions of cross-game rejection, explicit
edition-scoped build/ruleset/recommendation contracts, and AI schema exclusion
of canonical facts. Integration tests must prove exact ruleset resolution,
Policy Gate denial before success, transactional persistence, and no external
HTTP during user analysis.

See the [game-edition contracts](game-editions.md), [data provenance](data-provenance.md),
and [PoE1 finding ADR](../adr/0018-production-poe1-deterministic-findings.md).

