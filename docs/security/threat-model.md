# Threat Model

Status: implementation baseline, reviewed 2026-08-14 for policy-gate version
`1.0.0`. Revisit before the first public deployment and whenever an external
capability, parser, ruleset source, account system, or funding path changes.

## Security objectives

- Preserve deterministic correctness and evidence integrity.
- Prevent Lootwright from becoming a route to GGG account compromise, prohibited automation, scraping, or client inspection.
- Protect user-submitted build material and goals from unauthorized access or excessive retention.
- Prevent untrusted inputs, sources, dependencies, and AI output from executing code or changing canonical facts.
- Keep PoE1 and PoE2 data and rules isolated.
- Keep the service available under abusive or malformed workloads.

## Assets

- User goals, share codes, pasted item text, normalized snapshots, and analysis history.
- Ruleset packages, checksums, parser versions, provenance decisions, and deterministic results.
- Application secrets, database records, queue payloads, logs, and deployment credentials.
- Maintainer approval authority and source-register integrity.
- Permission evidence, effective periods, policy rules, kill switches, and
  append-only capability-decision audit records.
- The environment-only policy-admin token and emergency global kill-switch
  configuration.
- Project reputation and compliance posture.

GGG credentials are explicitly not assets held by Lootwright: `POESESSID`, Path of Exile passwords, browser cookies, session credentials, and game-client secrets must never be collected.

## Adversaries and failures

- An unauthenticated abuser exhausting CPU, memory, storage, queues, or AI quota.
- A malicious user submitting decompression bombs, parser exploits, XSS payloads, prompt injection, or cross-game identifiers.
- A compromised or careless maintainer activating an unproven source or altered ruleset.
- A compromised dependency, package registry, AI provider, or future documented API.
- An attacker exploiting authorization, tenant isolation, CSRF, SSRF, injection, cache confusion, or unsafe rendering.
- An honest contributor introducing nondeterminism, policy drift, or PoE1/PoE2 contamination.

## Trust boundaries

See the [system context](../architecture/system-context.md). The principal
boundaries are public browser input, Laravel-to-domain DTOs, the public
read-only policy explanation endpoint, the token-protected evidence-management
boundary, policy persistence, parser execution, ruleset activation,
persistence/Redis, operator imports, and optional outbound AI calls.

## Threats and required controls

| Threat | Example | Required controls |
| --- | --- | --- |
| Resource exhaustion | Huge base64 share code expands into a decompression bomb or anonymous callers fill storage | Encoded/decoded byte limits, expansion-ratio and 2-second parser budget, item/node/skill/gem limits, per-user/IP rate limits, authentication before persistence, bounded retention and retries |
| Parser exploitation | Malicious XML uses entities, DTD, deep nesting, or malformed numeric values | XXE/DTD/XInclude/network disabled, depth and count limits, strict schema/value objects, fuzz/property tests, no dynamic evaluation |
| SSRF and network pivot | User text contains a URL or redirect to internal services | Never fetch user-provided URLs; central outbound allowlist with scheme/host/port/path, DNS and redirect revalidation, private-address denial |
| XSS/content injection | Item text or AI prose contains HTML/script/Markdown payloads | Contextual escaping, sanitized allowlist Markdown only, CSP, no raw AI HTML, safe download content types |
| Prompt injection | Imported text tells AI to reveal secrets or invent rules | Treat content as data, minimize fields, structured schemas, no secrets/tools in AI context, deterministic authority, output validation |
| Canonical-data corruption | AI or source import creates a plausible stat mapping | AI excluded from canonical writes, two-person source/ruleset review, immutable versions, checksum verification, typed provenance |
| Cross-game confusion | PoE2 identifier resolves through PoE1 mapping or cache | Non-null game IDs, separate namespaces/catalogs/cache keys, database constraints, negative isolation tests, no fallback |
| Authorization failure | One user replays or deletes another user's import | Authentication condition at the persistence gate, keyed owner/idempotency hashes, owner-scoped idempotency, unguessable deletion capability, opaque IDs, policy and deletion tests |
| Sensitive-data leakage | Share code or item text appears in logs or provider prompts | Data classification, request hashes and coarse outcomes only in logs, no raw PoB persistence, encrypted consented normalized storage, no AI transmission, no analytics payloads |
| Credential collection | A feature asks for `POESESSID` to improve results | Product-level prohibition, blocked field names, security review, UI tests, no generic secret vault for GGG sessions |
| Prohibited automation | Recommendation feature clicks Trade or sends a whisper | No browser/client integration ports, no executable/extension deliverables, manual text recipe only, architecture and policy tests |
| Supply-chain compromise | Malicious Composer/npm package or install script | Lockfiles, `composer audit`, `npm audit`, provenance/license review, minimal dependencies, protected update review, pinned CI |
| Queue replay/duplication | Retried analysis creates inconsistent or excessive work | Idempotency keys, immutable inputs, bounded retry/backoff, per-workspace quotas, deterministic result keys |
| Ruleset rollback/tampering | Old or modified ruleset is activated silently | Content-addressed artifacts, SHA-256, immutable publication, activation audit, explicit supersession, rollback approval |
| CSRF/session abuse | Attacker submits or deletes analyses in a user's session | Laravel CSRF, secure/HTTP-only/SameSite cookies, session rotation, authorization on every mutation, rate limits |
| SQL/command injection | Imported text reaches a query or process | Parameterized queries, no shell execution in parsing, strict DTOs, least-privilege database role |
| Policy drift | An undocumented endpoint is added for convenience | Deny-by-default capability registry, allowlisted exact operations, source-register review, compliance tests and ADR |
| Gate bypass | A controller, job, UI path, or connector calls an external provider directly | Every external application use case depends on the owning `CapabilityPolicy` port; no UI authority; exact-operation tests; architecture review before adding a connector |
| Forged or stale evidence | An allow survives after its permission expires or is revoked | Effective periods, closed permission statuses, exact source versions, `require_review` as non-executable, transition tests for missing/unknown/expired/revoked/conflicting evidence |
| Emergency-control failure | A compromised source continues executing during an incident | Environment global kill switch plus persisted global/source/capability/source-capability switches; every active switch overrides an allow |
| Evidence-admin takeover | An attacker changes evidence or disables a kill switch | Environment-only high-entropy admin token, constant-time comparison, 404 fail-closed response, CSRF protection, rate limiting, no token in database/logs, deployment secret rotation |
| Audit data leakage | Raw builds, credentials, prompts, or personal data are written with decisions | Audit only source/version/capability/operation/outcome/reason/evidence IDs/non-secret condition names/time/actor type; tests reject token persistence |
| Public explanation overexposure | A read-only policy page reveals admin notes or secrets | Dedicated bounded response fields, no reviewer notes/tokens/audit context, read-only route, caching and rate limits |
| Deletion-token disclosure | A storage deletion token is recovered from the database, cache, or logs | Derive a 256-bit capability with the application key and owner-scoped high-entropy idempotency key, store only SHA-256, use constant-time verification, no-store responses, never log or include it in policy audits |
| Duplicate persistence | Client retry creates multiple encrypted records or changes a prior result | Required owner-scoped idempotency key, unique keyed hash, exact input/checksum/game/parser match, deterministic replay, conflict on key reuse |
| Parser format drift | A new upstream envelope/root is silently interpreted using old assumptions | Pinned source commits and license hashes, expiring evidence, exact root detection, parser versions, beta labelling, fail-closed unsupported structures |

## Privacy and retention baseline

- Collect only the content the user explicitly submits and the minimum operational metadata.
- Raw PoB share codes and XML are memory-only and are never persisted. Only a normalized result may be stored, and only after explicit consent.
- Authenticated and consented normalized PoB imports are application-key encrypted, default to 24 hours, cannot exceed the configured 168-hour ceiling, have owner-scoped idempotency and hash-only deletion capabilities, and are pruned hourly after expiry.
- Later workspaces and analysis history still require authenticated ownership, backup-aware deletion, and their own explicit retention policy.
- Do not use inputs or results to train models. Do not send them to AI unless the user enables the optional feature and the gate allows it.
- Logs use opaque correlation IDs and coarse metrics. No raw imports, full prompts, credentials, IP addresses beyond justified security retention, or protected game content.
- Backups inherit deletion and access controls; retention and restore-deletion behavior must be tested.

## Security verification before release

- Parser fuzzing and decompression-limit tests.
- Static analysis, dependency audits, secret scanning, and production configuration review.
- Authorization, CSRF, XSS, SSRF, SQL injection, rate-limit, queue replay, and deletion tests.
- Ruleset checksum/provenance and cross-game isolation tests.
- Table-driven default-policy and evidence-transition tests, exact-operation
  denial tests, all kill-switch scopes, admin-boundary tests, and audit-field
  review.
- AI-off, AI-timeout, prompt-injection, invalid-schema, and policy-denial tests.
- Manual review proving there is no GGG credential field, client/browser integration, scraper, undocumented endpoint, or funding entitlement.

## Residual and unresolved risks

- Format-only PoB1 and beta PoB2 interoperability are approved only for the pinned records through 2026-11-12; upstream drift or expired evidence disables execution.
- No public Lootwright account/login flow exists yet; persistent hosted imports remain unavailable to anonymous users until that separately reviewed boundary is implemented. Transient imports do not require an account.
- Public hosting jurisdiction, privacy notice, retention periods, age policy, and incident-response contacts are undecided.
- Third-party ruleset data may not be licensable for redistribution even if technically accessible.
- The precise boundary between factual compatibility text and protected GGG expression needs legal review.
