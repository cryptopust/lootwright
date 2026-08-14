# Manual Trade Workflow

Lootwright converts deterministic equipment-slot recommendations into a
human-readable recipe that a player can apply manually on the official Trade
site. It does not query Trade, reproduce an official search payload, inspect a
browser, or make a market claim.

Status: the compiler, closed DTO schema, canonical serialization, plain-text
renderer, Policy Gate operations, and fixture-only conformance tests are
implemented. Production recipes remain fail-closed until an approved immutable
PoE1 ruleset publishes the exact filter vocabulary and the deterministic
analyzer/prioritizer produces slot plans. PoE2 generation remains inactive.

## Workflow

1. The deterministic analyzer emits evidence-backed findings against one exact
   immutable ruleset.
2. The upgrade prioritizer associates a recommendation with a game-scoped
   equipment slot and deterministic filter intents. AI cannot add or alter an
   intent.
3. The application resolves an approved Trade vocabulary whose game, ruleset
   ID, version, parser version, patch, league, provenance, and checksum match
   exactly.
4. The Policy and Provenance Gate authorizes the local
   `trade.manual_recipe.generate` operation and the single generic
   `trade.homepage.link` operation. Missing or non-allow decisions stop output.
5. The edition adapter validates every label, range, conflict, constraint,
   dependency, and finding trace. It emits one strict recipe and one broad
   fallback recipe for each unique recommended slot.
6. The UI may render the canonical DTO or Lootwright's plain-text rendering. A
   copy action must require an explicit user click and copies only that plain
   recipe.
7. The player may click one clearly labelled generic homepage link and enter
   the filters manually.

## Recipe contents

Each slot recipe contains:

- game edition, platform realm, league, canonical slot, and optional budget;
- approved category and base-family labels when proven;
- required filters with canonical decimal minimum/maximum values;
- weighted optional filters with deterministic reasons and weights;
- explicit and ruleset-derived conflicting modifiers to exclude;
- an open-prefix or open-suffix preference only when the exact vocabulary
  contains the corresponding rule;
- corruption, influence, fracture, rarity, and similar constraints only when
  requested by the deterministic plan and supported by that exact edition,
  patch, league, and ruleset;
- a stricter variant and a broad fallback whose ranges and weights can only be
  relaxed, never tightened;
- dependencies on other game-scoped equipment slots;
- the originating finding and full explanation trace on every filter and
  dependency;
- ruleset ID/version/checksum, parser version, source/version, and confidence;
  and
- unresolved requirements with a clarification question.

Canonical modifier IDs in the DTO are Lootwright-internal, edition-scoped
identifiers used to preserve evidence. They are not official Trade stat IDs and
are never transformed into Trade query identifiers.

## Vocabulary and unresolved mappings

The renderer prints `exact_label`, `minimum`, `maximum`, and `weight` exactly as
provided by the checksum-bound approved vocabulary. It does not translate,
approximate, normalize against a live site, or select a similar-looking filter.

If a modifier, target family, constraint, affix preference, or automatically
conflicting modifier has no exact approved mapping, the compiler omits it from
executable filter lists, records an `unresolved_requirement`, and asks which
exact in-game filter label applies. It never fills the gap with an AI response,
another edition's vocabulary, a latest ruleset, or a guessed Trade identifier.

## Budget relaxation

The broad fallback is structurally constrained:

- a required filter may remain required, become weighted, or be omitted;
- a weighted filter may remain weighted with an equal or lower weight, or be
  omitted;
- an exclusion may remain excluded or be omitted;
- a broad numeric range must contain the strict range; and
- a broad plan cannot introduce a new requirement.

Invalid relaxation fails the whole recipe rather than silently changing user
priorities. No budget is converted into a price estimate, listing claim, or
availability claim.

## Allowed homepage links

The recipe DTO can expose exactly one generic, query-free link:

- PoE1: `https://www.pathofexile.com/trade`
- PoE2: `https://www.pathofexile.com/trade2` only after the PoE2 phase is
  activated; the current PoE2 generator refuses recipe generation.

The plain-text copy rendering deliberately excludes URLs. Link activation is a
separate explicit user action.

## Prohibited behavior

Lootwright does not:

- call or probe `/api/trade/search`, `/api/trade/fetch`, or
  `/api/trade/data/*`;
- generate encoded or query-bearing official Trade search URLs;
- fetch, display, rank, cache, monitor, or price live listings;
- scrape official or third-party Trade pages;
- read the clipboard, inspect browser state, or fill browser forms;
- open multiple searches automatically;
- send whispers, invitations, purchases, party actions, keyboard/mouse input,
  or in-game commands; or
- accept `POESESSID`, browser cookies, or Path of Exile credentials.

The Policy Gate explicitly denies live listing fetch and encoded URL generation
even if a future caller attempts to bypass the renderer.

## Presentation requirements

- Label the output as a manual recipe, not a live search or price check.
- Show unresolved requirements before inviting the user to apply filters.
- Show the ruleset/source identity and confidence next to the recipe.
- Preserve exact vocabulary labels and decimal strings; presentation may not
  recalculate or round them.
- Keep the generic homepage link visually separate from copy controls.
- Keep the required notice visible: "This product isn't affiliated with or
  endorsed by Grinding Gear Games in any way."
