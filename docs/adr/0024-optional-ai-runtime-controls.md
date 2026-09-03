# ADR 0024: Optional AI runtime controls and authority boundary

- Status: Accepted; production capabilities remain review-gated
- Date: 2026-08-21
- Extends: ADR 0004 and ADR 0012

## Context

Lootwright already had provider-neutral intent and explanation contracts, a
strict OpenAI Responses adapter, transactional spend accounting, and local
fallbacks. Production operation also needs independently controllable tasks,
persistent quotas, aggregate telemetry, and a circuit breaker without giving
an administrator, provider, or model authority over deterministic products.

## Decision

`IntentInterpreter` and `RecommendationExplainer` are narrow application ports;
`AiGateway` combines them for compatibility. The OpenAI adapter remains Laravel
infrastructure. It uses strict Structured Outputs, has no tools, does not store
provider application state, and treats refusals, incomplete responses, schema
failures, unknown terms, wrong-edition output, new product codes, and altered
facts as untrusted failures.

Execution requires every layer to permit the exact task: user opt-in, the
global environment switch, the task environment switch, the database runtime
switch, an executable Policy and Provenance Gate decision, the outbound
allowlist, a closed circuit, and all transactional budgets. Database controls
may lower environment budget ceilings but cannot raise them. A super-admin may
change runtime switches and lower global or per-user limits only after recent
password confirmation and mandatory admin 2FA; each mutation is rate-limited
and append-only audited.

Provider failures open a persistent circuit after the configured threshold.
After cooldown, one request reserves the half-open probe; success closes the
circuit and failure extends it. Cache entries are user-HMAC scoped, contain
only locally validated structured output, and use bounded expiry. Telemetry
contains opaque hashes and aggregate operational measurements, never prompts,
raw PoB, raw item text, or provider prose.

The AI-off path always remains complete: the manual intent form, deterministic
analysis, deterministic upgrade graph, Manual Trade recipe, and local template
explanations continue without a provider.

## Consequences

- UI switches cannot override environment lockdown, Policy Gate denial, egress
  denial, or hard environment budgets.
- AI can restate only the exact edition-scoped products supplied to it; it
  cannot persist a new finding or recommendation.
- Normal CI uses fake providers and no external request. A live smoke test is a
  separately confirmed operator action.
- Production activation still requires current provider/privacy review, exact
  Policy Gate evidence, an operator-owned API key, and external hard spend
  limits.

