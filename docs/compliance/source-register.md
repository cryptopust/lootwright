# Source Register

The register controls external facts, formats, APIs, and content. Absence from this file means disabled. An entry marked `policy-reference` may inform engineering policy but does not authorize importing or redistributing its content. An entry marked `candidate` is not approved for implementation.

Last reviewed and re-verified: 2026-08-14 at 13:16 UTC.

The Developer Docs, API Reference, and Terms were retrieved again from the
exact first-party URLs below. All returned HTTP 200. No material policy-text
change was identified against the earlier 2026-08-14 review: new application
registration remains unavailable, the documented-resource boundary and
non-affiliation notice remain, the named internal Trade paths remain absent
from the API Reference, and the Terms restrictions summarized below remain.
The privacy notice still says `Last Updated: October 2024`.

## Active records

| ID | Source and exact URL | Owner/provenance | Approved use | Redistribution | Status/review |
| --- | --- | --- | --- | --- | --- |
| `GGG-POLICY-001` | GGG Developer Docs: <https://www.pathofexile.com/developer/docs> | First-party GGG page, HTTP 200 retrieved 2026-08-14 | Policy requirements and documented-resource boundary only | Do not reproduce page content; concise factual policy notes and links only | `policy-reference`; re-read before release or integration change |
| `GGG-POLICY-002` | GGG API Reference: <https://www.pathofexile.com/developer/docs/reference> | First-party GGG page, HTTP 200 retrieved 2026-08-14 | Determine whether an API resource is documented; no endpoint is enabled for MVP | Do not mirror schemas/data without separate permission review | `policy-reference`; all API capabilities disabled |
| `GGG-POLICY-003` | GGG Terms of Use and Privacy Policy: <https://www.pathofexile.com/legal/terms-of-use-and-privacy-policy> | First-party GGG page, HTTP 200 retrieved 2026-08-14 | Define prohibited behavior and privacy/commercial constraints | Link and concise factual notes only | `policy-reference`; re-read before release or integration change |
| `USER-INPUT-001` | Natural-language goals explicitly entered in Lootwright | Directly supplied by the user for analysis | Constrained intent extraction and deterministic analysis configuration | Never publish or reuse; user-controlled retention | `allowed` subject to privacy/security controls |
| `USER-INPUT-002` | PoB/PoB2 share code explicitly pasted by the user | Directly supplied by the user; underlying format provenance is separate | Decode only after the applicable parser-format record is approved | Never publish raw input; short-lived processing by default | `conditional`; input accepted only when parser record is active |
| `USER-INPUT-003` | Path of Exile item text explicitly pasted by the user | Directly supplied by the user | Parse user-provided facts for that user's analysis | Never build a corpus or redistribute protected expression; short-lived raw retention | `allowed` with conservative parsing and display |
| `LOOTWRIGHT-001` | Lootwright-original rules, formulas, schemas, and documentation | Contributor-authored with review and evidence records | Runtime analysis and open-source distribution | MIT for original material only | `allowed`; must not encode unproven GGG facts |

## Candidate or disabled records

| ID | Candidate | Intended use | Missing approval evidence | Status |
| --- | --- | --- | --- | --- |
| `POB1-FORMAT-001` | Path of Building Community project, candidate repository <https://github.com/PathOfBuildingCommunity/PathOfBuilding>, HEAD `bcbca9b60b04abc17935c84ff3589342193bd758` retrieved 2026-08-14; pinned [MIT license](https://github.com/PathOfBuildingCommunity/PathOfBuilding/blob/bcbca9b60b04abc17935c84ff3589342193bd758/LICENSE.md), normalized retrieval SHA-256 `9d26ff7f9a40e544d0682ae0ae914b4a89cd16e8b0810f9b7bf03ea330601f24` | PoE1 share-code format and compatible parser fixtures | Verify maintainership and format scope, identify third-party/GGG-derived portions, record attribution, and complete third-party-license/redistribution review | `disabled-candidate`; repository pin and MIT evidence do not enable reuse |
| `POB2-FORMAT-001` | No repository selected | PoE2 share-code format | Exact authoritative repository, ownership, license, format versioning, provenance, and compatibility review | `disabled-candidate` |
| `POE1-RULES-001` | No ruleset source selected | Canonical PoE1 stats, calculations, patch/league rules | Exact documented GGG export or permitted third-party source, license/commercial terms, version, checksum, transformation and redistribution analysis | `disabled-candidate` |
| `POE2-RULES-001` | No ruleset source selected | Canonical PoE2 rules in phase two | Same evidence as PoE1 plus phase-two activation ADR | `disabled-candidate` |
| `GGG-API-MVP` | Documented resources under the API Reference | Potential future account or game metadata | Concrete product need, exact documented operation, application registration, scopes, retention, rate limits, approval | `disabled`; not needed for MVP |
| `GGG-TRADE-UNDOCUMENTED` | `/api/trade/search`, `/api/trade/fetch`, `/api/trade/data/*` | None | These are outside the allowed architecture | `permanently-prohibited` under current constitution |
| `GGG-PUBLIC-STASH` | Documented PoE1 Public Stashes resource | None for Lootwright | Live market indexing is a product non-goal even though the resource is documented | `disabled-out-of-scope` |
| `THIRD-PARTY-BUILD-SITES` | Any third-party build or trade site | None | Scraping is prohibited; API/license/provenance not selected | `disabled` |
| `POBBIN-REMOTE` | Remote <https://pobb.in/> fetching | Optional future retrieval of a user-selected build | Explicit operator permission, terms, retention, security review, exact host/path policy, and user consent | `disabled-candidate`; pasted codes remain supported |
| `REPOE-CANDIDATE` | RePoE candidate <https://github.com/brather1ng/RePoE> or a similar generated dataset | Candidate ruleset facts | Exact version, license chain, underlying GGG-data rights, commercial/derivative/redistribution permission, checksum, and attribution | `disabled-candidate`; hosted redistribution prohibited while rights are unknown |
| `AI-PROVIDER-001` | OpenAI API policy evidence: [data controls](https://developers.openai.com/api/docs/guides/your-data) and [spend limits](https://developers.openai.com/api/docs/guides/spend-limits), retrieved 2026-08-14 | Optional intent extraction and deterministic-result explanation | Configured credentials, user privacy disclosure, data minimization, provider approval, endpoint/model/region/retention decision, schema controls, opt-in UX, and enforced project or organization spend limit | `disabled-candidate`; current evidence is recorded but no provider adapter or allow decision exists |

## Record requirements

Before changing a candidate to `allowed`, add:

- exact owner, canonical URL, immutable version/commit, retrieval date, and checksum;
- license or written permission, including commercial use, modification, extraction, caching, and redistribution terms;
- which fields or transformations may be stored and displayed;
- required attribution and notices;
- game, patch, league, source version, and parser version applicability;
- security classification and retention/deletion behavior;
- automated update policy and fail-closed behavior;
- reviewer names/roles, review date, and expiry; and
- links to tests and an ADR when the capability boundary changes.

## Explicit exclusions

Lootwright's [MIT license scope](../../LICENSE-SCOPE.md) does not grant rights to any entry owned by GGG, a user, or a third party. Technical accessibility, user submission, factual nature, or an upstream repository's open-source license does not automatically prove that embedded publisher data/assets may be copied or redistributed.
