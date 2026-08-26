# Canonical data coverage

Status date: 2026-08-26. Counts are derived only from an active immutable
ruleset. Missing denominators are `unknown`; fixture or web counts are never
used as game-wide totals.

## Clean-install snapshot

This checkout contains no active production ruleset or imported database.
Therefore both editions currently have zero active records and unknown source
denominators in every category.

| Category | PoE1 imported / normalized / validated / active | PoE1 status | PoE2 imported / normalized / validated / active | PoE2 status |
| --- | --- | --- | --- | --- |
| character_class | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| ascendancy | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| skill_gem | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| support_gem | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| passive_node + keystone | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| item_base + unique_item | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| modifier_definition + stat_definition | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| tag / requirement | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| damage / ailment | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| offensive / defensive mechanics | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| reservation / attribute mechanics | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| jewel / cluster | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| edition mechanics / content goals | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |
| Trade vocabulary | 0 / 0 / 0 / 0 | BLOCKING | 0 / 0 / 0 / 0 | BLOCKING |

`DataCoverageReporter` exposes the same ledger fields as
`total_known_source`, `imported`, `normalized`, `validated`, `active`,
`missing`, and `knowledge_status`. Without a verified denominator,
`knowledge_status` remains `unknown`.

The PoE1 passive tree is a bounded approved vertical slice when the operator
imports the exact pinned GGG snapshot and passes validation. It does not make
skills, supports, items, modifiers, mechanics, or Trade vocabulary complete.
PoE2 remains independently unavailable until its own approved source, ruleset,
and staging acceptance exist.

See [data quality](data-quality.md) and the [source catalog](source-catalog.md).
