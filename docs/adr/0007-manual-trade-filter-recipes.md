# ADR 0007: Manual Trade-filter Recipes

- Status: Accepted
- Date: 2026-08-14

## Context

Users need help translating upgrade goals into a search strategy. Calling undocumented Trade endpoints, scraping Trade pages, generating opaque payloads, or automating browser interaction would violate product and policy boundaries. Live prices and listings are not required to teach a useful search plan.

## Decision

Lootwright emits a human-readable manual Trade-filter recipe only. A recipe contains descriptive item category, constraints, stat concepts and ranges, alternatives, priorities, relaxation order, and warnings. The user manually reproduces it in the official Trade UI.

Recipes contain no official Trade stat IDs, query payloads, undocumented endpoint calls, automatic browser actions, generated Trade search links, current prices, seller data, or availability claims. Lootwright-internal edition-scoped modifier IDs may appear only as evidence keys beside exact labels from the approved ruleset vocabulary; they are never encoded into an official Trade format. If a concept cannot be expressed confidently without an approved mapping, it is marked unresolved and requires clarification.

One clearly labelled, query-free link to the official game-specific Trade
homepage is allowed after the exact Policy Gate decision. Copy rendering
contains Lootwright's plain-text recipe only and requires an explicit user
action in presentation code.

## Consequences

- User agency and GGG-site separation are preserved.
- Results remain useful without network or market data.
- The recipe is less convenient than one-click search and may need clear UI education.
- Descriptive wording and mappings still require approved provenance and game-specific tests.
