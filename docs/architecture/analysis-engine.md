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

The framework-independent core also exposes the canonical analysis contracts:

- `AnalysisEngine` accepts an edition-scoped normalized build, `BuildIntent`,
  and `GameRuleset`, and returns an immutable `AnalysisResult`.
- `RuleRegistry` and `AnalysisRule` make the rule catalogue explicit and
  versioned. `Poe1RuleRegistry` delegates to the reviewed PoE1 rules; the PoE2
  registry is intentionally empty until an approved PoE2 ruleset exists.
- `Finding` carries stable finding identity, edition/ruleset aliases,
  unsupported data, dependencies, evidence, provenance, and explanation trace.
  The legacy persistence projection remains byte-compatible; the richer
  contract is emitted by `AnalysisResult`.
- `RecommendationCandidate` is a deterministic candidate DTO only. It does
  not create Trade IDs, prices, or links.

Upgrade planning is a separate deterministic stage. `DeterministicUpgradePlanner`
converts an `AnalysisResult` into an `UpgradeGraph` through an edition-specific
candidate factory. Each node declares prerequisites, conflicts, dependent
slots, affected finding IDs, expected effects, score, budget uncertainty, and
whether an approved market-data snapshot is required. Unknown prices remain
`requires-market-check`; no price is invented. Hard user constraints preserve
items, skills, and passive choices by excluding violating nodes into the
graph's explicit impossible list. Dependency ordering is topological and
cycles fail closed.

Manual Trade planning is the next deterministic stage. `TradeRecipeBuilder`
accepts only the selected `UpgradeCandidate`, its exact edition-scoped
`BuildSnapshot`, an approved compatible `GameRuleset`, and a separately
edition-scoped `TradeVocabulary`. `ModifierMatcher` verifies every filter
against both that vocabulary and the canonical modifier registry for the exact
ruleset. `ConstraintCompiler` emits human-readable broad and strict text only;
`RecipeValidator` rejects API paths and request-like output. Unknown mappings
remain typed `unsupported_filters`. PoE2 vocabulary is disabled and cannot
borrow PoE1 identifiers.

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
       (approved imported dataset + approved provenance + compatibility)
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

`Poe1AnalysisEngine` is the provider-neutral orchestration facade used by the
core contract. It rejects cross-edition inputs, refuses non-approved rulesets,
and discloses unsupported or unknown properties instead of converting them
into facts. `Poe2AnalysisEngine` fails closed with an explicit unavailable
result; it never borrows PoE1 rules or identifiers.

## Current limitations

- Production execution produces findings but does not yet persist the new
  upgrade graph or recipe products. The planner and manual recipe engine are
  production-domain implementations, but approved production modifier data and
  ruleset-owned Trade vocabulary are still required before they can emit
  actionable filters. Demo screens remain explicitly fixture-backed.
- There is no PoE2 deterministic engine, PoE2 ruleset, or PoE2 passive-tree
  source adapter. PoE2 input cannot fall back to PoE1.
- Canonical contracts exist for skills, support gems, item bases, uniques,
  modifiers, stats, and content goals, but no approved importer currently
  supplies those datasets. Production reads return unavailable rather than
  using UI/test fixtures.
- PoB normalization is intentionally structural and does not copy Path of
  Building formulas or bundled data. Unsupported fields remain disclosed.
- Character catalog facts are version-controlled code metadata rather than
  linked governed source snapshots.
- Result UI demo routes remain fixture-oriented; owner-scoped analysis pages
  exist, but the complete production findings/recommendation experience is not
  yet the release-ready product flow.

## AI authority boundary

The explanation request is constructed from existing typed findings and
recommendations that must share one analysis and one edition. Its strict schema
fixes array length, edition, and exact existing codes plus plain explanation
text. Extra canonical-fact properties are rejected. After schema validation,
code order, numeric claims, canonical-code references, and forbidden content
are checked again; any violation falls back to deterministic text. The
before/after domain objects remain unchanged.

Natural-language intent uses the separate `IntentInterpreter` port and a
closed, exact-edition vocabulary. `RecommendationExplainer` can only explain
the already-created products. Both are optional; the deterministic parser and
manual form run without a provider. See [ADR 0024](../adr/0024-optional-ai-runtime-controls.md).

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
