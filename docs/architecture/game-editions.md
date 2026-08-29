# Game Edition Contracts

Lootwright models Path of Exile 1 and Path of Exile 2 as separate rules domains
behind shared contracts. `Lootwright\Domain\Shared\Game\GameEdition` is the one
canonical identity type:

```text
poe1
poe2
```

Adding a second string enum, boolean such as `isPoe2`, or unscoped “game” field
would create an ambiguous parallel identity and is prohibited.

## Architecture support versus release activation

The architecture recognizes both editions so stored identities, parsers,
future adapters, and negative tests can remain explicit. PoE2 now has an
independent source, ruleset, parser, engine, policy path, and release gates;
public availability remains independently controlled until those gates are
evidenced.

Any public route or UI that accepts PoE2 before that approval is a release-scope
defect, not proof that PoE2 analysis is implemented.

## Shared contracts

Shared contracts may define identity, lifecycle, evidence, and orchestration,
but not game facts:

| Contract | Required edition behavior |
| --- | --- |
| `GameScope` | Combines `GameEdition` with an edition-compatible platform realm. |
| `EditionScopedValue` and identifier subclasses | Equality includes concrete type, edition, and value. Serialization always includes edition. |
| `BuildSnapshot` | Build ID, scope, patch, league, parser version, and catalog must agree on edition. |
| `CanonicalBuild` | Snapshot and ruleset must match edition, patch, league, and parser exactly. |
| `RulesetIdentity` | ID, version, patch, league, parser, and provenance are edition-scoped and checksum-bound. |
| `Finding` | Analysis ID, rule reference, trace, and canonical build share an edition and ruleset. |
| `Recommendation` | Carries an explicit edition; its analysis, findings, and explanation trace must match. The trace contains the exact ruleset ID/version. |
| Manual recipe | Recipe, recommendation, filters, slot, and evidence trace share an edition. |
| Source snapshot | Database record has non-null `game_edition`, source identity, revision, checksum, schema, permission context, and immutable normalized content. |
| Canonical game entity | Identity is edition + immutable ruleset + entity type + stable external ID; source provenance must have the same edition. |
| `GameRuleset` / `GameVersion` | Publication classification, provenance, and compatibility are explicit; fixture or unavailable data cannot activate. |

Application DTOs may enclose these types. A DTO containing untyped normalized
arrays must still carry a top-level edition, validate every game-specific value
inside its adapter, and reject mismatches before deterministic execution.

## Adapter ownership

```text
src/GameAdapters/PoE1/
  Analysis/
  PassiveTree/
  Pob/
  TradePlanning/

src/GameAdapters/PoE2/
  Pob/
  TradePlanning/       # fail-closed until approved
```

The architecture test suite forbids PoE1 adapters importing PoE2 adapters and
vice versa. Shared application code cannot import a concrete game adapter.
Laravel infrastructure may route by `GameEdition`, but each branch must call a
separate adapter or return a typed unavailable result. Shared formulas require
independent evidence of equivalence for both games and an architectural review.

## Import and analysis rules

1. Detect the edition from validated format evidence; never infer it with AI.
2. Compare detected and requested editions before persistence or analysis.
3. Normalize only through the matching adapter.
4. Store the edition on the artifact, normalized snapshot, build, analysis,
   outbox intent, source snapshot, ruleset, and activation scope.
5. Resolve a ruleset by exact edition, patch, league scope, and parser version.
6. Reject a cross-edition identifier even when its local slug is identical.
7. Execute only the matching deterministic engine. An unavailable engine fails
   visibly and never delegates to the other edition.
8. Recommendations and recipes retain the analysis edition and exact ruleset
   evidence; AI cannot add either product.

## Persistence contract

PostgreSQL stores portable string values `poe1` and `poe2`, while PHP uses the
backed enum. Database checks constrain governed source/ruleset lifecycle rows.
Workflow repositories compare the queued edition to the persisted edition when
claiming work. Composite identities use the edition wherever the same external
or catalog value could exist in both games.

Some child projection tables inherit edition through `analysis_id` instead of
duplicating it. Their encrypted canonical payloads retain the edition/ruleset
trace. Reads must traverse the owning analysis and must not infer edition from a
human-readable code.

`canonical_game_data` enforces this boundary with edition-matched composite
foreign keys to `ruleset_versions` and `source_snapshots`. Character and
Ascendancy relationships are ruleset-local. Repository reads require edition
and ruleset ID, so a shared external ID cannot cross the boundary even when two
games use the same display name.

## Current adapter state

PoE1 has a bounded PoB reader, official passive-tree importer, exact ruleset
resolver, and narrow production deterministic finding engine. PoE2 has an
independent versioned PoB2 reader, checksum-gated canonical dataset/ruleset, deterministic
finding engine, upgrade factory, and manual recipe adapter. Unsupported
mechanics fail closed rather than borrowing PoE1 behavior.

See the [current-state audit](../audits/current-state.md) and the existing
[PoE1/PoE2 boundary](poe1-poe2-boundary.md).
