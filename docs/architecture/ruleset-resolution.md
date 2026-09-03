# Ruleset resolution contract

Build game identity, immutable Lootwright ruleset identity, parser identity,
and source snapshot identity are separate. Resolution uses edition, semantic
patch, league scope, and parser compatibility through
`PostgresRulesetResolver::resolveActive`; internal ruleset suffixes are not
compared to PoB game versions. Published, approved, compatible, checksum-valid
records are required. Unknown metadata, wrong patch, incompatible parser,
inactive/fixture records, cross-edition records, and checksum mismatches fail
closed. Ruleset objects are read directly from PostgreSQL rather than stored as
serialized cache values; only scalar active IDs may be cached.
