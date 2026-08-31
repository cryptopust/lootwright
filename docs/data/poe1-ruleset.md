# PoE1 minimum production ruleset

The initial production ruleset is an immutable, checksum-addressed snapshot of
the official GGG passive-tree export. It contains character classes,
ascendancies, passive nodes, and keystones, plus the reviewed deterministic
analysis manifest. The source is not copied from PoB, RePoE, PoEDB, or scraped
pages.

Source revision: `8bd138b32ea2631455cac5935bfab089f826094f`  
Patch: `3.29.1`  
Source SHA-256: `7e9f755e33152129ebf36c2ebdad639c527e4ad70d274b1fefb860f30ca01122`  
Parser compatibility: PoE1 `1.0.0`  
Analysis manifest: engine `1.0.0`

Supported facts are the normalized PoB character identity, level, attributes
when reported, life/energy shield/mana, resistances, armour/evasion,
reservation, skill groups, equipped slots, passive IDs, keystones, and the
reviewed deterministic rules listed in the engine registry. Every finding
retains ruleset and source provenance.

The snapshot does not claim complete game coverage. Unsupported or unproven
mechanics remain explicit uncertainty; they never become fabricated modifiers,
prices, Trade identifiers, or recommendations. Skill-gem and item modifier
vocabulary is emitted only when an approved canonical vocabulary exists.

Activation is append-only and reversible by activating a previously published
ruleset. Published payloads and source snapshots are immutable; checksum,
edition, parser compatibility, uniqueness, and provenance are verified before
activation.
