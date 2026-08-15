# Funding Policy

Status: funding off by default; reviewed 2026-08-15. This engineering policy is
not legal advice or evidence of GGG/OpenAI approval.

Lootwright launches and operates without donations, subscriptions, advertising, sponsorship, affiliate links, paid tiers, token sales, or other monetization. No payment provider, donation button or link, entitlement table, billing secret, sponsor tracking, or funding telemetry may be added while this status is active. A read-only funding-status page may explain the long-term sustainability goal and the disabled policy state, but it may not solicit funds, link to a payment destination, collect interest, or imply future benefits.

## Permanent equality rule

Funding must never unlock, improve, extend, or prioritize:

- functionality or adapters;
- analysis accuracy, rules, explanations, or recommendations;
- request, storage, AI, or export quota;
- queue priority, support priority, early access, uptime, or performance;
- source/ruleset freshness; or
- access to the application, code, data, or community governance.

If donations are later permitted, they are unconditional support for the same open product. A donor receives no digital or in-game benefit, influence over findings, private feature, commercial data right, or GGG-related entitlement.

## Activation prerequisites

Funding remains disabled until a dated, documented review resolves all of:

1. GGG API policies, Terms of Use, intellectual-property, trademark, and non-commercial constraints for the proposed model.
2. Independent legal advice where policy language is ambiguous.
3. Project entity, tax, accounting, sanctions, consumer-law, refund, and payment-provider obligations.
4. Privacy, fraud, chargeback, security, data retention, and incident-response impacts.
5. Transparent public accounting and governance appropriate to the model.
6. Confirmation that funding does not change access, quota, accuracy, priority, adapters, or source permissions.
7. An accepted ADR and updated [source register](source-register.md), threat model, privacy notice, and user-facing copy.
8. A named evidence record, dated policy-decision ID, explicit operator action,
   versioned public disclosure, and executable `allow` from the deny-by-default
   Policy and Provenance Gate.

Silence, common community practice, provider availability, or the open-source license is not approval. If review cannot establish permission, funding stays off.

## Enforcement

- The codebase has no funding capability by default.
- An informational funding-status page must render no payment, donation,
  sponsorship, affiliate, waitlist, or contact-to-fund action.
- Configuration alone cannot enable funding; activation requires reviewed code and documentation.
- `FUNDING_ENABLED` is only an operator request. Missing or malformed activation
  metadata, a non-`allow` Policy Gate result, or the absence of a reviewed
  payment implementation keeps both `enabled` and `accepting_funds` false.
- Tests must prove funding state cannot affect authorization, feature flags, queue priority, results, or quotas.
- Maintainers must remove or disable funding immediately if GGG policy, law, or provider terms change.
- Operating-cost projections use configuration-only aggregate assumptions and
  must never join player, build, account, or future supporter data.

## Evidence review on 2026-08-15

The current GGG Terms page was retrieved from the exact first-party URL at
2026-08-15 20:26:27 UTC (HTTP 200; response-body SHA-256
`8acc7ccf100a595b499d949cab01bba429f60f265ae53177a41c6e760588f77b`). The
personal/non-commercial licence and prior-written-approval restrictions
summarized in the [GGG integration policy](ggg-integration-policy.md) remained
present. This is a conservative engineering observation, not legal approval.

No actual GGG support correspondence or written permission concerning
Lootwright funding exists in the repository or source register. The official
developer-page statement that new application registrations are unavailable is
a web-page policy record, not a private support reply and not funding approval.
Silence, an unanswered inquiry, or a summary without preserved primary evidence
must never be represented as permission. If correspondence is received later,
the redacted primary record, date, sender authority, exact scope, checksum, and
review decision must be added before it can influence policy.

The [OpenAI request package](../sponsorship/openai-one-pager.md) is an unsent
draft. Official program terms say application does not guarantee selection,
funding, API credits, access, or sponsorship. The package creates no runtime
provider permission and no funding permission.

## Unresolved questions

- Whether any donation model is compatible with GGG's current non-commercial language for Lootwright's exact use.
- Which legal entity, jurisdiction, and accounting model would apply.
- Whether public sponsorship acknowledgements could imply endorsement or commercial promotion.
