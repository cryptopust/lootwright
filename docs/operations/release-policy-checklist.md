# GGG and OpenAI Pre-Release Policy Checklist

Complete this checklist for the exact release SHA and production environment. Record
reviewer, UTC time, primary evidence URL/checksum, decision, expiry, and follow-up.
Checking a box is not legal approval, GGG approval, OpenAI sponsorship, credit, or
program eligibility.

## GGG and product boundary

- [ ] Re-read the current first-party GGG Developer Docs, API Reference, and Terms;
  update retrieval time/hash and source register before release.
- [ ] Confirm no current/revoked correspondence is misrepresented as permission and no
  unavailable application registration or OAuth credential is assumed.
- [ ] Run guardrails and denial tests proving undocumented Trade endpoint strings exist
  only in binding policy/deny tests, no connector calls them, and no live listing,
  price, scraping, encoded URL, automation, overlay, extension, or client inspection
  exists.
- [ ] Confirm runtime/UI asks for no `POESESSID`, GGG password, browser cookie, session,
  or unnecessary OAuth token.
- [ ] Review the production asset inventory: only Lootwright-original code/vector/UI
  fixtures; no GGG/PoE logo, artwork, item/passive image, screenshot, music, flavour
  text, font, or copied dataset.
- [ ] Verify the exact non-affiliation notice is visible and compatibility wording does
  not imply approval, partnership, authorization, or endorsement.
- [ ] Confirm every enabled parser/source/ruleset record is current, checksum-bound,
  within approved purpose, and unexpired. Missing rulesets keep analysis unavailable.
- [ ] Confirm funding, payments, advertising, affiliates, sponsorship placements, and
  monetization links remain absent and `FUNDING_ENABLED=false` cannot affect output.

## OpenAI boundary

- [ ] If AI stays off, confirm `OPENAI_ENABLED=false`,
  `OUTBOUND_NETWORK_ENABLED=false`, empty `OPENAI_API_KEY`, Policy Gate non-executable,
  deterministic fallback tests, and no live eval/smoke task in CI. No further provider
  credential is needed for health or release.
- [ ] If a future reviewed release proposes AI activation, re-read official Responses,
  Structured Outputs, model, pricing, data-controls, rate-limit, error, and spend-limit
  documentation; update source evidence and privacy/cost disclosures.
- [ ] Verify explicit per-workflow user opt-in, minimum redacted typed context,
  `store:false`, no tools/browsing/files, strict schemas, exact ruleset vocabulary,
  single repair, deterministic recommendation immutability, and template fallback.
- [ ] Verify endpoint/host/path/method allowlist, public DNS checks, redirects off,
  deployment egress firewall, project hard spend limit, local daily/monthly budgets,
  token caps, timeout, and terminal billing/policy failures.
- [ ] Confirm no claim of Zero Data Retention, sponsorship, endorsement, free credits,
  program selection, or eligibility without preserved primary evidence.

## Deployment decision

- [ ] Full CI, PostgreSQL migration sanity, production image build/inspection, parser
  security, Policy Gate, fast eval, browser, dependency audit, secret/compliance
  guardrails, backup freshness, and isolated restore evidence pass for the release SHA.
- [ ] Privacy jurisdiction/controller/security contacts, age policy, breach timelines,
  backup provider terms, RPO/RTO, and legal blockers are resolved.
- [ ] Image digest, database migration plan, previous-image rollback, active queue
  worker lifecycle when applicable, capability state, on-call owner, and incident
  switches were exercised in staging.
- [ ] No external resource, image publication, domain change, or deployment proceeds
  without explicit operator authorization.
