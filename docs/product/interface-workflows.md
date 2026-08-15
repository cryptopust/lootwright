# Interface Workflows

Lootwright's Inertia 3 and Vue 3 interface presents application results without
performing authoritative calculations. The visual direction is an original
"Forge Ledger" system built from the Obsidian, Bone, Ember, and Arcane palette,
local system font stacks, CSS geometry, and semantic HTML. It contains no GGG or
Path of Exile logos, art, item icons, passive-tree imagery, screenshots, copied
interface patterns, remote fonts, or decorative publisher assets.

Status: the complete responsive product flow is implemented with clearly
labelled fixture data. Production findings, upgrades, and recipes remain
fail-closed until approved immutable PoE1 rulesets and the production
deterministic slice are available. PoE2 remains format-review only.

## Route map

| Route | Purpose |
| --- | --- |
| `/` | Explain what Lootwright does, what it refuses to do, and how deterministic analysis differs from optional AI wording. |
| `/analyses/new` | Four-step local validation wizard for edition, pasted input, build goal, budget context, privacy, AI opt-in, and review. |
| `/analyses/demo/import` | Show detected edition, parser warnings, compatibility, unsupported features, and the fixture boundary. |
| `/analyses/demo/overview` | Show build identity, skills, defences, slots, confidence, and version status. |
| `/analyses/demo/findings` | Group findings by severity and category with a keyboard-native evidence disclosure. |
| `/analyses/demo/upgrades` | Show deterministic priority, dependencies, budget context, source, confidence, and limitations. |
| `/analyses/demo/trade` | Show strict and broad manual recipes, exact fixture vocabulary, explicit plain-text copy, and one generic official PoE1 Trade homepage link. |
| `/analyses/demo/provenance` | Show ruleset, source, parser, analysis version, hashes, and exact Policy Gate outcomes. |
| `/analyses/demo/states` | Exercise loading, empty, partial, stale-ruleset, AI-disabled, policy-denied, and error states. |
| `/privacy`, `/data-deletion`, `/methodology`, `/limitations`, `/non-affiliation` | Explain data handling, deletion, deterministic methodology, product limits, and independence. |
| `/usage` | Show only the current user's AI usage and the disabled-provider state. |
| `/funding` | Explain open-source/unaffiliated status, policy-disabled funding, configuration-driven hosting/AI projections, future aggregate reporting, and permanent equality without rendering a solicitation, monetization/social link, payment action, waitlist, donor state, or benefit. |

## Wizard behavior

The wizard keeps preview input in browser component state and makes no analysis
request. It rejects inputs shorter than the local minimum, refuses an explicit
PoE1/PoE2 marker conflict, and requires a meaningful goal plus deliberate
processing consent before the fixture review. Choosing PoE2 shows that only
format review is available and that no ruleset, finding, upgrade, or recipe will
be produced.

The privacy step separates normalized-result retention, optional AI wording,
AI cache permission, and processing consent. AI is off by default and the UI
states that it cannot perform calculations or change deterministic results.

## Evidence and recommendation contract

Every fixture recommendation exposes:

- its originating finding codes and explanation;
- exact ruleset and source references;
- confidence expressed as a labelled percentage, not an unexplained score;
- dependencies and budget context without price or availability claims; and
- a limitation that remains visible or one explicit `Why?` action away.

The renderer consumes typed fixture DTOs. It does not derive defences, rank
upgrades, resolve game identifiers, round recipe values, translate vocabulary,
or promote AI wording into deterministic evidence.

## Manual Trade boundary

The recipe surface follows the [manual Trade workflow](manual-trade-workflow.md):

- strict and broad variants use exact approved fixture labels and decimal
  strings;
- copy writes only Lootwright's own plain-text recipe after a user click and
  never reads the clipboard;
- the copied text contains no URL;
- the only outbound Trade link is the query-free PoE1 homepage; and
- no live listing, price, Trade ID, encoded search, automated form, whisper,
  purchase, or browser action exists.

## Localization and accessibility

Turkish is the default locale foundation. The persistent TR/EN switch changes
interface wording and the document language while edition, values, evidence,
and policy identity stay unchanged.

The implementation targets WCAG 2.2 AA with:

- a skip link, semantic landmarks and heading order;
- native buttons, inputs, fieldsets, legends, lists, tables, and disclosures;
- visible Arcane focus rings and keyboard-operable navigation;
- text and symbols in addition to state colors;
- live regions for validation and copy outcomes;
- readable line lengths and contrast on the original dark palette;
- reduced-motion support; and
- layouts verified without horizontal overflow at 390 by 844, 768 by 1024,
  and 1440 by 1000 pixels.

## Test strategy

Vitest and Vue Test Utils cover edition identity, shell landmarks and notice,
wizard validation, edition conflicts, PoE2 inactivity, consent, evidence
disclosure, exact recipe variants, and explicit clipboard writes. Playwright
runs Chromium critical flows against fixture-only routes, checks localization
and policy-safe Trade behavior, asserts horizontal containment, and compares
committed visual references at the three representative viewport sizes.

The required notice is persistent in the application footer:
"This product isn't affiliated with or endorsed by Grinding Gear Games in any
way."
