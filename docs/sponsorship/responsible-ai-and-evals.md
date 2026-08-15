# Responsible AI and Reproducible Evals

Status: proposed evaluation plan backed by the existing fake-transport test
harness. It is not an OpenAI certification or endorsement.

## Allowed AI responsibilities

1. Produce a typed natural-language `BuildIntent` candidate.
2. Ask short clarification questions when confidence is insufficient.
3. Render Turkish or English prose for findings and recommendations already
   present in deterministic output.

AI is not the source of truth for PoB parsing, calculations, canonical IDs,
modifiers, rules, prices, sources, URLs, recommendations, policy, or ranking.
Generated language is labelled and escaped as text.

## Evaluation sets

Versioned, synthetic fixtures cover:

- clear, ambiguous, mixed-language, colloquial, and incomplete intents;
- valid, malformed, refused, timed-out, and rate-limited provider responses;
- schema-invalid output, extra properties, invalid enums, and unknown IDs;
- prompt injection in character names, notes, goals, item text, and findings;
- Turkish/English meaning preservation and concise clarification;
- budget exceeded, provider disabled, egress denied, and Policy Gate denied;
- cache hit/miss equivalence and one-repair-attempt enforcement; and
- snapshots proving the final deterministic recommendation set, order, IDs,
  evidence, and numeric values are byte-identical with AI on or off.

No normal test or CI job uses a live key. A separate manual smoke command is
explicitly confirmed, single-request, and caller-budget-capped.

## Metrics and release gates

| Dimension | Measure | Release condition |
| --- | --- | --- |
| Schema validity | Strict validation success | No invalid payload reaches application DTOs |
| Reference integrity | Unknown or cross-edition terms | Zero accepted unknown references |
| Recommendation integrity | Canonical deterministic snapshot diff | Zero changes |
| Clarification quality | Answerable, concise, non-leading questions | Fixture expectations pass in both locales |
| Injection resistance | Forbidden instruction compliance | Zero policy or recommendation overrides |
| Privacy | Fields sent and audit fields stored | Exact allowlist; no raw prompt by default |
| Cost | Input/output caps and reserved maximum | Every call fits configured local budgets |
| Degradation | Disabled/refused/timeout path | Deterministic result remains available |

Human review samples explanation fidelity and accessibility but cannot authorize
new game facts. A failed eval blocks prompt/model/template promotion. Model,
schema, prompt-template, and ruleset versions are recorded independently so a
regression can be reproduced.

## Reporting

Public reports would contain aggregate pass/fail counts, fixture versions,
known limitations, and token distributions without player text or personal
data. Safety incidents follow the project [incident response runbook](../security/incident-response.md).
