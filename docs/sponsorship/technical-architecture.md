# Technical Architecture for an OpenAI Review

Status: factual architecture summary, 2026-08-15. This document does not imply
OpenAI review, approval, sponsorship, or program eligibility.

## System boundary

Lootwright is a Laravel 13 modular monolith with PostgreSQL, Redis queues, and
Horizon. Framework-independent PHP under `src/` owns the deterministic domain
and application ports. Laravel infrastructure under `app/` owns HTTP, database,
queue, storage, Policy Gate, and provider adapters. The Vue/Inertia layer renders
typed results and performs no authoritative calculation.

```text
user input
  -> bounded local parser
  -> immutable normalized snapshot + exact ruleset identity
  -> deterministic findings and upgrade ranking
  -> manual Trade recipe
  -> optional AI wording over a minimum, already-determined projection
```

PoE1 and PoE2 have separate adapters and canonical identifier spaces. Every
analysis records edition, parser version, ruleset checksum, normalized input
hash, deterministic output hash, evidence, provenance, and policy state.

## Provider-neutral AI gateway

Application ports define intent extraction and explanation operations without
HTTP or OpenAI types. The OpenAI adapter uses the official Responses API. It
sends strict JSON Schemas for `BuildIntent`, `ClarificationSet`, and
`ExplanationBundle`, rejects extra properties and invalid enums, disables
tools, limits output tokens, and requests no persistent response storage.

The gateway sequence is:

1. run the deterministic intent parser;
2. check explicit user opt-in, Policy Gate, egress switch, rate limits, and cost
   budgets;
3. deduplicate a privacy-permitted normalized request;
4. send only required redacted fields;
5. validate the strict response and resolve every term against the selected
   edition/ruleset;
6. permit at most one tightly bounded repair attempt;
7. use deterministic clarification or explanation templates on any failure.

The official [Responses API reference](https://developers.openai.com/api/reference/resources/responses/methods/create)
and [Structured Outputs guide](https://developers.openai.com/api/docs/guides/structured-outputs)
are the schema authorities. The application does not use provider output as
HTML and does not expose provider tools or arbitrary URLs.

## Security, privacy, and operations

- OpenAI, outbound networking, imports, rulesets, and external links have
  independent emergency switches.
- Outbound access is deny by default and pins the exact HTTPS host, port, and
  Responses path; redirects, userinfo, query strings, and private/reserved DNS
  answers are denied.
- API keys and raw prompts are excluded from logs. Local metadata stores only
  provider, model, prompt-template version, token usage, latency, cache status,
  validation outcome, and bounded cost.
- Per-user and per-IP identifiers are HMAC pseudonyms. Raw PoB, private notes,
  sessions, and unrelated personal data are never sent to the provider.
- AI failure is graceful degradation and cannot fail deterministic analysis.

See the project [AI gateway operations](../operations/ai-gateway.md), [data
flow](../architecture/data-flow.md), and [security baseline](../security/security-baseline.md).
