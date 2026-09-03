# Privacy Policy

Status: implementation baseline for the optional AI Gateway, reviewed 2026-08-21. Public-hosting jurisdiction, controller contact details, age policy, and backup-provider terms remain release blockers.

## Optional AI processing

AI is off by default and is never required to receive deterministic findings, recommendations, manual Trade recipes, or template explanations. A future UI must obtain an explicit user opt-in before each permitted AI workflow. Lootwright sends only the minimum typed context needed for intent extraction, clarification, or explanation. It does not send PoB payloads, session data, credentials, complete private notes, canonical ruleset packages, or unrelated personal information.

The OpenAI adapter sends requests to `POST /v1/responses` with `store: false`, no tools, bounded token limits, and a non-identifying hashed safety identifier. Lootwright does not store raw prompts or raw provider responses by default. Local audit records contain only opaque hashes, prompt-template version, provider/model, token counts, latency, cache status, validation outcome, repair count, and integer micro-USD cost. The admin dashboard receives aggregate usage and cost only; it does not receive prompts, provider output, raw PoB, or raw item text.

OpenAI's [official data-controls documentation](https://developers.openai.com/api/docs/guides/your-data) states that API data is not used to train models unless the customer explicitly opts in. It also states that default abuse-monitoring logs may retain customer content for up to 30 days. `store: false` disables Responses application-state storage; it does not by itself provide Zero Data Retention. Lootwright does not claim Zero Data Retention or Modified Abuse Monitoring unless OpenAI approves and the deployment documents that configuration.

## Cache, retention, and deletion

Normalized local response caching is permitted only when the user-facing privacy choice allows it. Cache keys are HMACs of normalized, minimal request data; cache entries contain only schema-validated candidate or explanation fields and expire after one hour by default. Raw input is never used as a cache key or written to logs.

User-scoped AI audit and daily-budget records use one-way deployment-key HMACs rather than account IDs or IP addresses. They are operational records, not analysis authority. The owner-deletion workflow removes the user's linkable AI audit, daily-budget, and indexed cache entries; global aggregate spend counters remain unlinkable. Production retention and backup deletion must still be configured and tested before activation.

Runtime controls and per-user quota overrides retain account foreign keys for
administration and deletion, but contain no prompt or build content. Runtime
changes are limited to super-admins, require recent password confirmation and
admin 2FA, and create an append-only reasoned audit record.

Queued analysis may use an authenticated account or an expiring anonymous
privacy session. An anonymous credential contains a UUIDv7 plus a random
256-bit secret and is shown only when created. Lootwright stores only the
secret's SHA-256 hash, status, expiry, and lifecycle timestamps; it does not
store an IP address, browser fingerprint, or user agent as the anonymous
identity. Possession of the credential authorizes access to that session's
builds, so clients must treat it as sensitive. Expiry or deletion makes it
unusable.

Raw pasted artifacts are encrypted in private object storage only for the
bounded queue handoff and are removed after parsing or terminal rejection, with
a one-hour expiry ceiling. Normalized snapshots and deterministic products are
encrypted and owner-scoped. Build deletion cascades its snapshots, analyses,
findings, recommendations, recipes, provenance, policy decisions, and optional
explanation. Full-session deletion also invalidates the privacy credential.
Only unlinkable aggregate deletion counts remain in the primary database;
backup purge and restore-time deletion replay remain release prerequisites.

See the [threat model](security/threat-model.md), [AI operations runbook](operations/ai-gateway.md), and [cost controls](operations/ai-cost-controls.md).
The exact live, cache, log, session, AI, and backup schedule is maintained in
[data retention](security/data-retention.md).
