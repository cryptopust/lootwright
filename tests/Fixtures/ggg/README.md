# Official GGG passive-tree fixture

`passive-tree-8bd138b-reduced.json` is a deliberately reduced structural
fixture derived on 2026-08-20 from the root `data.json` in Grinding Gear Games'
official [`skilltree-export`](https://github.com/grindinggear/skilltree-export)
repository at commit `8bd138b32ea2631455cac5935bfab089f826094f`
(upstream file SHA-256
`7e9f755e33152129ebf36c2ebdad639c527e4ad70d274b1fefb860f30ca01122`).

The fixture retains only the minimum factual shape needed to exercise class,
Ascendancy, node type, stats, graph connection, mastery, secondary-progression,
and icon-reference normalization. It excludes sprites, images, flavour text,
layout data, and other third-party content. The fixture is test-only and its
presence does not extend Lootwright's MIT license to GGG-owned data. Required
notice: This product isn't affiliated with or endorsed by Grinding Gear Games
in any way.
