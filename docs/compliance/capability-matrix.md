# Capability Matrix

Status: binding deny-by-default baseline, policy version `1.0.0`, reviewed
2026-08-14. A `require_review` result is non-executable and cannot be treated as
an allow by a UI, administrator, feature flag, AI provider, or fallback path.

## Decision semantics

| Decision | Production effect |
| --- | --- |
| `allow` | Execution is permitted only for the exact source, source version, capability, and operation when current `allowed` evidence exists, every named condition is supplied by the trusted application boundary, and no kill switch matches. |
| `deny` | Execution is prohibited. |
| `require_review` | Execution is prohibited until a reviewer records current evidence and replaces the rule with an explicit reviewed allow. |

Missing rules and unknown source versions deny. Missing, unknown, expired,
revoked, denied, or conflicting evidence never enables execution. Revoked,
denied, and conflicting evidence produces a denial; missing, unknown, expired,
or unmet conditions require review but remain non-executable.

## Conservative defaults

| Source | Capabilities and exact operation family | Baseline | Conditions or rationale |
| --- | --- | --- | --- |
| Lootwright manual Trade schema | `derivative_analysis: trade.manual_recipe.generate` | `allow` | Requires deterministic input, one exact resolved ruleset, manual actions only, and no market data. This permits only Lootwright-original local serialization. |
| Lootwright manual Trade schema | `link_out: trade.homepage.link` | `allow` | Requires an explicit user action and exactly one generic, query-free official Trade homepage link. |
| Lootwright manual Trade schema | `link_out: trade.encoded_url.generate` | `deny` | Encoded or query-bearing official Trade search URLs are prohibited. |
| Lootwright manual Trade schema | `live_fetch: trade.listings.fetch` | `deny` | Recipes cannot fetch, display, rank, cache, monitor, or price live listings. |
| User-pasted PoB/PoB2 code | `import`, `transient_process` | `allow` | Requires `explicit_user_submission`; parser provenance remains a separate gate. |
| User-pasted PoB/PoB2 code | `persistent_store` | `allow` | Additionally requires `user_storage_consent` and `authenticated_user`; retention, idempotency, owner scoping, and deletion controls still apply. |
| User-pasted PoB/PoB2 code | `public_display`, `redistribution` | `deny` | User input is private and non-redistributable by default. |
| User-pasted item text | `import`, `transient_process` | `allow` | Requires `explicit_user_submission` and hostile-input bounds. |
| User-pasted item text | `persistent_store` | `allow` | Additionally requires `user_storage_consent` and `authenticated_user`. |
| User-pasted item text | `public_display`, `redistribution` | `deny` | User input is private and non-redistributable by default. |
| Governed user snapshots | `import: user.pob.snapshot.import`, `user.item_text.snapshot.import` | `allow` | Explicit user submission only; snapshots remain private and are denied as ruleset authority. |
| Official PoE1 skill tree | `import: ggg.poe1.skilltree.snapshot.import`, `ruleset.source.activate` | `allow` | Exact documented export, operator workflow, PoE1 scope, immutable payload and verified checksum are mandatory. |
| Official PoE1 Atlas tree | snapshot import and activation | `require_review` / `deny` | The family is recorded as allowed in principle but is outside the current MVP. |
| Official documented GGG APIs | `live_fetch` | `require_review` | No exact API operation is enabled. A future operation needs available application registration, configured credentials, least-privilege scopes, and current policy evidence. |
| GGG application registration | `live_fetch: ggg.application.register` | `deny` | On 2026-08-14 the official docs still state that GGG is unable to process new applications. |
| Undocumented Trade endpoints | `live_fetch` for `/api/trade/search`, `/api/trade/fetch`, and the `/api/trade/data/*` family | `deny` | These paths are absent from the supported API Reference. Exact unregistered paths also fail the missing-rule default. |
| `POESESSID`, passwords, cookies, sessions | `import`, `persistent_store` | `deny` | Lootwright must never request, capture, transmit, or retain account secrets. |
| Official site/forum/Trade scraping | `live_fetch` | `deny` | Automated extraction is prohibited. |
| Browser extensions, overlays, client/file/memory/network/screen/log inspection, macros, and automation | `transient_process` | `deny` | Client interaction and automated input or Trade behavior are prohibited. |
| Remote pobb.in fetching | `live_fetch: pobbin.fetch` | `require_review` | Disabled until explicit remote-fetch permission evidence and user consent are reviewed. Pasted codes remain supported separately. |
| Path of Building Community, pinned PoE1 format | `derivative_analysis: pob.community.format_interpret` | `allow` | Independent local format interpretation only; requires configured attribution, the pinned commit, and no copied upstream implementation. |
| Path of Building Community, pinned PoE2 format | `derivative_analysis: pob2.community.format_interpret` | `allow` | Same format-only conditions; PoE2 output is beta and cannot activate rulesets or analysis. |
| Path of Building Community, broader reuse | `import`, `derivative_analysis`, `redistribution: pob.community.reuse` | `require_review` | Source, game data, formulas, assets, full builds, dependencies, and third-party portions remain disabled. |
| RePoE or similar generated datasets | `import: repoe.snapshot.import`, `ruleset.source.activate` | `deny` | Runtime snapshot import and ruleset authority are prohibited under the current source decision. |
| RePoE or similar generated datasets | other `import`, `derivative_analysis` candidates | `require_review` | Underlying data rights and current GGG policy must be documented before any superseding review. |
| RePoE or similar generated datasets | `redistribution`, `monetized_hosting` | `deny` | Hosted redistribution remains disabled while underlying rights are unresolved. |
| poe.ninja public economy API | `live_fetch: poe_ninja.economy.leagues.fetch`, `poe_ninja.economy.exchange.fetch`, `poe_ninja.economy.stash_item.fetch` | `allow` | Exact documented PoE1 economy paths and configured category allowlist only; HTTPS/exact host, cache headers, operator contact/User-Agent, source switch, evidence, and kill switches are mandatory. |
| poe.ninja builds/profile/auth/page operations | `live_fetch` | `deny` | Builds, profiles, characters, PoB, authentication, scraping, site replication, unknown paths, and user-controlled URLs are outside this approval. |
| PoE Wiki Cargo | `live_fetch: poe_wiki.cargo.factual_metadata.fetch` | `require_review` | Disabled pending CC BY-NC-SA/share-alike, GGG-data, attribution, redistribution, and funding review. |
| PoE Wiki Cargo snapshot lifecycle | `import: poewiki.cargo.snapshot.import`, `ruleset.source.activate` | `require_review` | `POEWIKI_IMPORT_ENABLED=false` additionally fails closed and cannot turn review into allow. |
| poe.ninja economy snapshot lifecycle | `import: poeninja.economy.snapshot.import` | `allow` | Both independent source switches, current evidence, operator contact, exact allowlist and normalized-snapshot-only conditions are required. |
| poe.ninja economy ruleset authority | `import: ruleset.source.activate` | `deny` | Market context cannot define deterministic game rules. |
| GGG art, item images, logos, music, flavour text, screenshots, and fonts | `public_display`, `redistribution` | `deny` | Protected publisher expression is outside Lootwright's license scope. |
| OpenAI Responses API | `live_fetch: openai.responses.intent`, `openai.responses.explanation` | `require_review` | A tool-free, stateless, strict-schema adapter is implemented and off by default. Execution remains prohibited until privacy/opt-in UX, provider approval, current evidence, configured credentials, and deployment hard spend limits are reviewed. |
| Funding activation request | `monetized_hosting: lootwright.funding.activate` | `deny` | `FUNDING_ENABLED` and operator metadata cannot override the exact denied rule/evidence. A superseding dated review, allowed evidence, accepted ADR, operator action, and visible disclosure are all required. |
| Donations and monetized hosting | `monetized_hosting: lootwright.donations`, `lootwright.hosting` | `deny` | Requires an explicit policy/legal decision; funding cannot affect functionality, quota, results, ranking, visibility, or access. |

## Kill switches

The environment-level `POLICY_GLOBAL_KILL_SWITCH` fails closed before any allow.
The database also supports global, source, capability, and combined
source-capability switches. Any active matching switch overrides an allow rule.
Only the protected policy-admin boundary may manage evidence or database kill
switches.

## Audit and explanations

Every gate request records source, source version, exact operation, capability,
decision, reason, policy version, evidence IDs, non-secret condition names, time,
and actor type. It does not record raw input, credentials, tokens, prompts, or
unnecessary personal data.

The public read-only source explanation endpoint exposes source metadata and
human-readable rules. Evidence management and kill-switch mutation require the
policy-admin token, CSRF protection, and rate limiting. The token is configured
only through the environment and is never persisted in an audit or evidence
record.

The PoB HTTP intake requests user `import` and `transient_process` decisions
before decoding. Once the XML root supplies structural edition evidence, it
requests the exact pinned PoB1 or PoB2 format-interpret decision before the
game-specific normalizer runs. Persistence requests a separate
`persistent_store` decision with explicit user consent and an authenticated
Lootwright owner. Missing authentication produces a non-executable
`require_review` decision. This condition refers only to Lootwright identity;
GGG sessions and credentials remain denied.

## Current official evidence

- GGG Developer Docs: <https://www.pathofexile.com/developer/docs>
- GGG API Reference: <https://www.pathofexile.com/developer/docs/reference>
- GGG Terms: <https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy>
- OpenAI API data controls: <https://developers.openai.com/api/docs/guides/your-data>
- OpenAI API spend limits: <https://developers.openai.com/api/docs/guides/spend-limits>
- OpenAI Responses API: <https://developers.openai.com/api/reference/resources/responses/methods/create>
- OpenAI Structured Outputs: <https://developers.openai.com/api/docs/guides/structured-outputs>
- OpenAI GPT-5.4 nano: <https://developers.openai.com/api/docs/models/gpt-5.4-nano>
- OpenAI API pricing: <https://developers.openai.com/api/docs/pricing>

See the [source register](source-register.md) for retrieval and provenance
details. These links are evidence references; they do not independently enable
any connector.
