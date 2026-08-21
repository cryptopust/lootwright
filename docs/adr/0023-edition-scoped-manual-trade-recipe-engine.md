# ADR 0023: Edition-scoped Manual Trade Recipe Engine

- Status: Accepted
- Date: 2026-08-21

## Context

Deterministic upgrade candidates need a safe translation into steps a player
can reproduce manually on the official Trade site. An upgrade recommendation
does not prove a Trade filter label or identifier. Similarly named modifiers
across PoE1 and PoE2 are not interchangeable. The active release remains PoE1;
PoE2 must fail closed until its independent ruleset and vocabulary are
approved.

## Decision

`TradeRecipeBuilder` accepts an immutable `UpgradeCandidate`, `BuildSnapshot`,
`GameRuleset`, and edition-specific `TradeVocabulary`. It requires the exact
edition, ruleset ID, version, checksum, patch, approved provenance, compatible
ruleset, enabled vocabulary, and canonical modifier-registry record to agree.

`Poe1TradeVocabulary` and `Poe2TradeVocabulary` are separate adapters. The
PoE2 adapter is present only as a disabled contract. A vocabulary entry carries
an internal canonical modifier ID, a human-readable in-game label, and source
provenance. An optional documented identifier is evidence only; the recipe
engine never serializes it as a query or request payload.

Strict recipes preserve approved minimums and weights. Broad recipes may only
relax required filters into optional human-readable guidance and remove numeric
minimums. Unknown modifiers, missing structured requirements, and unproved
conflict mappings become `unsupported_filters`; they are never guessed.
Declared positive modifier conflicts fail closed. Other-slot dependencies are
shown as review warnings without asserting an unproved stat loss.

The result has no Trade API request format, listing data, seller action, price
claim, search URL, or automation instruction. The presentation may expose the
generic official Trade homepage after the existing Policy Gate decision and a
single explicit user click. Copying includes only the selected broad or strict
plain-text recipe.

## Consequences

- Canonical modifier imports and approved vocabulary data are prerequisites for
  actionable filters.
- Production candidates without structured approved requirements remain
  visibly unsupported rather than being converted from prose.
- PoE1 identifiers cannot enter a PoE2 recipe; PoE2 generation remains
  unavailable in the current release.
- No external request is made while building or displaying a recipe.

This decision extends [ADR 0007](0007-manual-trade-filter-recipes.md) and does
not authorize any undocumented Trade endpoint.
