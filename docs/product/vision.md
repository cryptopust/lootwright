# Product Vision

Status: future product vision. The repository is a working pre-alpha foundation;
production game analysis remains blocked on an approved ruleset and analyzer.

Lootwright is intended to become an open-source web application for people who
want to improve a Path of Exile build without surrendering judgment to an opaque
model or an automated trading tool. No public `lootwright.org` service exists.

The user deliberately supplies a natural-language goal plus a PoB/PoB2 share code or pasted item text. Lootwright normalizes that input with a game-specific parser, evaluates it against an immutable versioned ruleset, and returns:

1. deterministic build findings with evidence;
2. prioritized upgrade recommendations with explicit reasons and trade-offs; and
3. a manual Trade-filter recipe the user can reproduce themselves.

Path of Exile 1 is the MVP. Path of Exile 2 is a planned second adapter, not a second mode hidden inside PoE1 logic. AI is optional: it may turn natural language into constrained intent or explain deterministic output, but it is never the authority for game facts, identifiers, calculations, prices, or links.

Lootwright succeeds when a user can understand what is wrong, why an upgrade matters, and how to search manually—even when AI and all external integrations are disabled. Trust comes from reproducibility, visible evidence, explicit uncertainty, source provenance, and conservative policy enforcement.

Lootwright is not a bot, price-check overlay, live market indexer, game-client tool, browser automation tool, or trading automation system. It will not scrape sites, inspect the game or the user's computer, or act on the user's behalf.

The public product must visibly display:

> This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

See [MVP scope](mvp-scope.md), [non-goals](non-goals.md), and [GGG integration policy](../compliance/ggg-integration-policy.md).

## Principles

- Deterministic before generative.
- Evidence before confidence.
- Manual user agency before automation.
- PoE1 correctness before PoE2 breadth.
- Verified permission before capability.
- A useful offline core before external convenience.
- Small, operable architecture before speculative scale.

## Assumptions and open questions

- We assume users have the right to submit their own build and item text for analysis.
- PoB and PoB2 format provenance and license compatibility must be verified before parser implementation.
- GGG's current developer page says new application registrations cannot be processed. The MVP therefore assumes no GGG OAuth or API dependency.
- Commercial-use, donations, trademarks, and any redistribution of canonical game data require documented legal/policy review. Until then, they remain disabled.
