# Build regression laboratory corpus

This corpus contains project-created, real-shaped Path of Building XML
documents. It is not copied from a player account, does not contain a share
code, and is not production game data. Names and mechanics are used only to
exercise the locally implemented parser/rule boundaries. Every case declares
its expected edition, parser facts, deterministic findings, and unsupported
properties in `manifest.json`.

The corpus is deliberately split by edition. PoE2 cases only assert parser
isolation and the currently approved fail-closed analysis result; they do not
claim PoE2 mechanics that Lootwright has not approved.

Golden files are reviewed artifacts. Tests fail on missing, unexpected, or
reordered findings and never rewrite a golden snapshot automatically.

Required notice: This product isn't affiliated with or endorsed by Grinding
Gear Games in any way.
