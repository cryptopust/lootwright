# Non-goals

Lootwright deliberately excludes the following from the MVP and from the product's intended direction unless the engineering constitution is replaced through explicit policy and legal review.

## Platform interaction

- No game bot, gameplay assistant, macro engine, chat tool, whisper sender, invite manager, purchase flow, or party automation.
- No keyboard or mouse automation.
- No game process, memory, file, screen, clipboard, network-traffic, or log inspection.
- No executable companion, injected component, overlay, price-check overlay, or browser extension.
- No browser automation or automatic manipulation of the official Trade site.

## Data and market behavior

- No scraping of the official site, forums, Trade pages, or third-party build sites.
- No reverse engineering or use of undocumented GGG endpoints, including `/api/trade/search`, `/api/trade/fetch`, and `/api/trade/data/*`.
- No live market indexing, price prediction, price checking, listing aggregation, seller ranking, or automated availability claims.
- No invented canonical IDs, Trade IDs, item IDs, stat mappings, prices, or links.
- No collection of `POESESSID`, Path of Exile passwords, browser cookies, or session credentials.
- No redistribution of GGG logos, artwork, music, item art, flavour text, or other protected assets.

## Product behavior

- No AI-authored game truth. AI cannot decide formulas, rules, findings, ranking scores, or authoritative filters.
- No requirement for AI or any external provider to complete the core workflow.
- No automatic Trade query or one-click Trade link. The output is a manual recipe described for a human.
- No mixing PoE1 and PoE2 rulesets or shipping partial PoE2 behavior under a generic ARPG abstraction.
- No paywall, paid quota, donor accuracy, adapter access, priority queue, or other funding-linked advantage.

## Architecture

- No microservices, Kafka, Kubernetes, event sourcing, distributed database, data lake, or speculative multi-region design for the MVP.
- No separate service merely to isolate AI, parsers, queues, or game adapters.
- No dependency added only for novelty or to avoid writing a small, testable domain abstraction.

These exclusions are product safety properties, not a backlog of implied future features.

