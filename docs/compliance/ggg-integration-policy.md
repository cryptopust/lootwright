# GGG Integration Policy

Status: binding, deny by default. This document is an engineering policy based
on sources retrieved and re-verified on 2026-08-14 at 13:16 UTC; it is not legal
advice. The re-verification found no material policy-text change from the
earlier review that day, so no capability was broadened or enabled.

## Sources reviewed

| Source | Exact URL | Retrieved | Result |
| --- | --- | --- | --- |
| GGG Developer Docs, including API Policies, Third-Party Requirements, available resources, developer guidelines, errors, rate limits, and authorization | <https://www.pathofexile.com/developer/docs> | 2026-08-14 13:16 UTC | HTTP 200, title `Developer Docs - Path of Exile`; registration remains unavailable |
| GGG API Reference | <https://www.pathofexile.com/developer/docs/reference> | 2026-08-14 13:16 UTC | HTTP 200, title `Developer Docs - Path of Exile`; named internal Trade paths absent |
| Path of Exile & Path of Exile 2 Terms of Use and Privacy Policy | <https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy> | 2026-08-14 13:16 UTC | HTTP 200, title `Path of Exile`; privacy notice still states last updated October 2024 |

The official pages can change without notice. Re-read and record them before enabling or materially changing any GGG-facing capability, and at least before each public release.

## Source findings that govern Lootwright

The Developer Docs state that:

- applications must comply with the API policies, Terms of Use, Privacy Policy, user privacy, and documented developer guidelines;
- supported resources are only those defined in the API Reference or listed in Data Exports;
- requests for internal website APIs or in-game resources outside the documentation will be denied, and reverse-engineering undocumented endpoints violates Terms section 7i;
- websites are the safest application form, while applications interacting with the game or game files violate the Terms;
- API clients must use an identifiable OAuth user agent, protect credentials, handle errors, and dynamically obey response rate-limit headers and `Retry-After`;
- public applications must visibly state: "This product isn't affiliated with or endorsed by Grinding Gear Games in any way."; and
- at retrieval time, GGG says it is unable to process new application registrations.

The Terms prohibit, without prior written approval, automated software or bots, data-gathering/extraction tools against the Website, unauthorized server connections, modification of the game client or its data, and reverse engineering of the Website, Materials, or Services. The Terms also restrict reproduction, storage, distribution, display, and derivative works from protected material, and describe the granted license as personal and non-commercial.

These are conservative engineering interpretations. If a proposed use is ambiguous, the capability remains disabled pending documented GGG permission and legal review.

## Allowed product behavior

- Accept natural-language goals, PoB/PoB2 share codes, and item text that the user deliberately pastes.
- Parse approved formats locally on Lootwright servers, subject to verified format provenance and license.
- Analyze user-supplied facts using Lootwright-original deterministic code and approved, versioned rulesets.
- Return evidence-backed findings, upgrade recommendations, and a descriptive manual filter recipe.
- Use Path of Exile names only as reasonably needed to identify compatibility.
- Link to the official home page in ordinary text if useful, without logos or proprietary graphics.
- Use an optional AI provider solely for constrained intent extraction or explanation, independent of GGG systems.

## Prohibited behavior

- Calling, probing, documenting from observation, reproducing, or reverse-engineering undocumented endpoints. `/api/trade/search`, `/api/trade/fetch`, and `/api/trade/data/*` are expressly prohibited even if they are visible in browser traffic or used by the official Trade site.
- Scraping the official website, forums, Trade pages, or any third-party build site.
- Live market indexing, price checking, listing aggregation, automatic Trade queries, automatic Trade links, or availability claims.
- Collecting `POESESSID`, Path of Exile passwords, browser cookies, OAuth tokens not required by an approved documented feature, or session credentials.
- Inspecting the game process, memory, files, screen, clipboard, network traffic, or logs.
- Automating keyboard input, mouse input, gameplay, chat, whispers, invites, purchases, party actions, or browser interaction.
- Shipping a browser extension, executable overlay, injected tool, or downloadable client companion.
- Copying or redistributing GGG logos, artwork, music, item art, flavour text, or other protected content.
- Presenting Lootwright as approved, authorized, partnered, or endorsed by GGG.
- Treating accessibility of data, an open-source repository, or Lootwright's MIT license as permission to use GGG material.

## Policy and Provenance Gate

Every external capability is denied unless an active decision record matches all of:

- capability and exact operation;
- owner and maintainer;
- game and product phase;
- exact source URL/host/path/method or offline artifact;
- source version and retrieval date;
- provenance chain and SHA-256 checksum when content is imported;
- license/permission and permitted purpose;
- commercial-use status;
- required attribution and notices;
- data classification, retention, and redistribution rules;
- authentication/scopes, rate limits, user agent, and caching rules when applicable;
- expiry/review date; and
- approving reviewers.

Unknown, missing, conflicting, expired, or checksum-mismatched fields deny the operation. Redirects, new hosts, new endpoints, new methods, broadened scopes, and new data fields are new operations requiring review.

Gate denials cannot be overridden by AI, environment variables, feature flags, user input, administrator convenience, or error fallback. Emergency disablement may revoke an allow record immediately.

The executable decision semantics and seeded defaults are recorded in the
[capability matrix](capability-matrix.md). `require_review` is non-executable.

## GGG API posture

The MVP has no GGG API dependency. The documented API Reference includes resources such as profiles, filters, leagues, characters, PoE1 stashes/public stashes, and currency exchange, but documented availability alone is not a product need or permission to build a live market feature. These capabilities remain disabled.

If a future feature genuinely requires a documented API:

1. re-read all three official sources above;
2. confirm application registration and required scopes are available;
3. add the exact operation to the [source register](source-register.md);
4. obtain policy/legal and security approval;
5. implement OAuth without passwords or session cookies;
6. use `api.pathofexile.com`, exact documented routes, an identifiable user agent, least privilege, and secure token storage;
7. honor all response rate-limit headers and `Retry-After` dynamically; and
8. fail closed if documentation, permission, or policy changes.

## Branding and public notice

The exact statement below must be visible in the site footer and on About/legal surfaces, without being hidden behind interaction:

> This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

Do not use GGG or game logos, trade dress, artwork, item art, or a visual treatment that implies an official service. Compatibility wording should be factual and subordinate to the Lootwright brand.

## Commercial and funding posture

Unknown commercial-use status is disabled. Advertising, subscriptions, sponsorships, affiliate links, paid access, and donations are disabled until a documented review permits them. Even if donations are later allowed, they cannot unlock functionality, quota, accuracy, adapters, priority, or access. See the [funding policy](funding-policy.md).

## Review triggers

Re-review this policy when GGG changes its docs/Terms, a parser or source changes, PoE2 is activated, an API/OAuth feature is proposed, AI receives new data, hosting jurisdiction changes, funding is considered, or protected content is proposed for storage/display.
