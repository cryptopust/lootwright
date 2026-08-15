# ADR 0004: Provider-neutral Optional AI Port

- Status: Accepted
- Date: 2026-08-14

## Context

Natural-language intent and readable explanations may benefit from an AI model, but provider availability, privacy terms, cost, and model behavior can change. AI output is nondeterministic and cannot safely define game truth.

## Decision

Expose a provider-neutral AI application port with two allowlisted capabilities:

1. extract user intent into a closed, validated schema; and
2. explain deterministic results without changing their facts, ordering, certainty, or evidence.

Provider adapters live in infrastructure. Calls require explicit policy approval, data minimization, redaction, timeout, schema validation, and an optional user-facing setting. Deterministic defaults and template explanations cover disabled or failed AI.

ADR 0012 selects the first infrastructure adapter while preserving this provider-neutral boundary.

AI may not invent canonical stat IDs, item IDs, rules, calculations, prices, Trade IDs, or links, and may not write rulesets or normalized facts.

## Consequences

- Lootwright remains useful offline and avoids provider lock-in.
- Provider evaluation can focus on a small, testable contract.
- Some nuanced goals require user confirmation rather than model guesswork.
- AI output is clearly labeled and carries provider/model metadata when shown.
