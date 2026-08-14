# ADR 0007: Manual Trade-filter Recipes

- Status: Accepted
- Date: 2026-08-14

## Context

Users need help translating upgrade goals into a search strategy. Calling undocumented Trade endpoints, scraping Trade pages, generating opaque payloads, or automating browser interaction would violate product and policy boundaries. Live prices and listings are not required to teach a useful search plan.

## Decision

Lootwright emits a human-readable manual Trade-filter recipe only. A recipe contains descriptive item category, constraints, stat concepts and ranges, alternatives, priorities, relaxation order, and warnings. The user manually reproduces it in the official Trade UI.

Recipes contain no canonical Trade stat IDs, query payloads, undocumented endpoint calls, automatic browser actions, generated Trade links, current prices, seller data, or availability claims. If a concept cannot be expressed confidently without an unapproved ID or source, it is marked unsupported.

## Consequences

- User agency and GGG-site separation are preserved.
- Results remain useful without network or market data.
- The recipe is less convenient than one-click search and may need clear UI education.
- Descriptive wording and mappings still require approved provenance and game-specific tests.

