# ADR 0012: OpenAI Responses Adapter Behind the Optional AI Port

- Status: Accepted for implementation; production capability remains `require_review`
- Date: 2026-08-15

## Context

ADR 0004 permits optional provider-neutral intent extraction and explanation but did not select a concrete adapter. The MVP needs a testable implementation without making AI authoritative, mandatory, or a path around policy, privacy, or spend controls.

## Decision

Implement an OpenAI adapter under Laravel infrastructure using the Responses API and strict Structured Outputs. Use Laravel's existing HTTP client because OpenAI's official SDK catalog currently has no official PHP SDK. Keep schemas, DTOs, validation, fallback, and orchestration provider-neutral under `src/Application/AIGateway`.

Requests are stateless (`store: false`), tool-free, token-bounded, redacted, policy-gated, opt-in, and transactionally budgeted. Provider output is untrusted: local schema and exact-reference validation precede use. One schema repair is the maximum. The OpenAI source rules remain `require_review`; configuration cannot enable production calls without a reviewed policy change.

## Consequences

- Deterministic analysis and template explanations remain the complete fallback.
- No OpenAI package or provider type enters the deterministic domain.
- Provider changes can reuse the application contracts and safety controls.
- A future activation requires privacy/opt-in UX review, operational limits, current evidence, and an explicit Policy Gate allow in the same reviewed change.
