# Threat Model

Status: implementation hardening baseline, reviewed 2026-08-15 for policy-gate
version `1.0.0`. Controls are mapped in the
[security baseline](security-baseline.md). Revisit before the first public deployment and whenever an external
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
boundaries are public browser input, Laravel security-header/rate-limit
middleware, Laravel-to-domain DTOs, the public
read-only policy explanation endpoint, the token-protected evidence-management
boundary, policy persistence, parser execution, ruleset activation,
persistence/Redis, operator imports, the deny-by-default egress guard, and
optional outbound AI calls.

## Threats and required controls

| Threat | Example | Required controls |
| --- | --- | --- |
| Resource exhaustion | Huge base64 share code expands into a decompression bomb or anonymous callers fill storage | Encoded/decoded byte limits, expansion-ratio and 2-second parser budget, item/node/skill/gem limits, per-user/IP rate limits, expiring privacy sessions, bounded retention and retries |
| Parser exploitation | Malicious XML uses entities, DTD, deep nesting, or malformed numeric values | XXE/DTD/XInclude/network disabled, depth and count limits, strict schema/value objects, fuzz/property tests, no dynamic evaluation |
| SSRF and network pivot | User text contains a URL or redirect to internal services | Never fetch user-provided URLs; central exact-operation scheme/host/port/path allowlist, public DNS answer validation, no query/userinfo, private-address denial, and redirects disabled |
| XSS/content injection | Item text or AI prose contains HTML/script/Markdown payloads | Contextual escaping, sanitized allowlist Markdown only, CSP, no raw AI HTML, safe download content types |
| Prompt injection | Imported text tells AI to reveal secrets or invent rules | Treat content as data, minimize fields, structured schemas, no secrets/tools in AI context, deterministic authority, output validation |
| AI authority expansion | Provider prose adds a recommendation, canonical term, price, source, or URL | Closed schemas, exact edition/patch vocabulary resolution, exact finding/recommendation code and order checks, forbidden-content rejection, deterministic snapshot tests |
| AI spend race or retry storm | Concurrent requests reserve the same budget or billing-limit `429` loops | Transactional per-user/IP/global reservations, monthly circuit breaker, bounded transient-only retries, `Retry-After`, terminal billing/quota codes, provider hard spend limit |
| AI retention surprise | Stateless request is mistaken for zero retention | `store: false`, no raw local prompt/response audit, explicit disclosure of default abuse-monitoring retention, no ZDR claim without provider approval |
| Canonical-data corruption | AI or source import creates a plausible stat mapping | AI excluded from canonical writes, two-person source/ruleset review, immutable versions, checksum verification, typed provenance |
| Cross-game confusion | PoE2 identifier resolves through PoE1 mapping or cache | Non-null game IDs, separate namespaces/catalogs/cache keys, database constraints, negative isolation tests, no fallback |
| Authorization failure | One principal replays, exports, or deletes another principal's build | Account or privacy-session authorization, hashed anonymous secret, keyed owner/idempotency hashes, owner-scoped idempotency, opaque IDs, constant-time verification, policy/export/deletion tests |
| Sensitive-data leakage | Share code or item text appears in logs or provider prompts | Data classification, request hashes and coarse outcomes only in logs, raw input only in the encrypted expiring object handoff, encrypted normalized storage, no AI transmission, no analytics payloads |
| Credential collection | A feature asks for `POESESSID` to improve results | Product-level prohibition, blocked field names, security review, UI tests, no generic secret vault for GGG sessions |
| Prohibited automation | Recommendation feature clicks Trade or sends a whisper | No browser/client integration ports, no executable/extension deliverables, manual text recipe only, architecture and policy tests |
| Supply-chain compromise | Malicious Composer/npm/base image or install script | Lockfiles, `composer audit`, `npm audit`, provenance/license review, minimal dependencies, protected update review, pinned CI actions/versioned base tags, non-root production image, digest-only deployment, and no image push in CI |
| Release-policy drift | A secret, prohibited GGG asset/dataset, undocumented endpoint connector, session credential field, payment package, or enabled funding/AI default enters a release | Dependency-free tracked/untracked repository guardrail, dedicated denial tests, production configuration preflight, original-asset allowlist, and exact release checklist |
| Deployment/proxy mistake | Mutable image, spoofed forwarding header, plaintext data-store link, public Horizon, or automatic breaking migration | Reviewed immutable release identity, exact trusted host/proxy entries, edge header stripping, verified PostgreSQL and Redis/Valkey TLS when enabled, local-only Horizon gate, separate migration operation, expand/contract and previous-release rollback |
| Backup recovery exposure | A restore resurrects deleted data or becomes reachable before verification | Encrypted bounded backups, `_restore_verify` target guard, isolated no-egress restore, forward migrations, deletion/retention pruning, count/hash checks, and quarterly recorded exercises |
| Queue replay/duplication | Retried or concurrently published analysis creates inconsistent or excessive work | Transactional outbox, locked publisher rows, bounded publisher/job retry, edition/ruleset job identity, owner-scoped keyed idempotency hashes, atomic state claims, optimistic completion, immutable result hashes |
| Raw queue handoff exposure | A queued share code survives a crash or is copied into a queue/database payload | Application-key encryption in private object storage, opaque key only in PostgreSQL, ID-only queue payload, immediate post-parse deletion, one-hour expiry ceiling, hourly pruning, no raw logs or backups |
| Ruleset rollback/tampering | Old or modified ruleset is activated silently | Content-addressed artifacts, SHA-256, immutable publication, activation audit, explicit supersession, rollback approval |
| CSRF/session abuse | Attacker submits or deletes analyses in a user's account/session | Laravel CSRF and secure cookies for accounts; high-entropy expiring bearer credential for anonymous sessions; authorization on every read/mutation; rate limits; no token logging |
| Browser injection/isolation failure | Stored notes or provider prose execute, or the site is framed | Escaped Vue/Inertia text, no raw HTML, strict production CSP, frame denial, MIME sniffing denial, HSTS, no-referrer and permissions policies, same-origin opener isolation |
| SQL/command injection | Imported text reaches a query or process | Parameterized queries, no shell execution in parsing, strict DTOs, least-privilege database role |
| Policy drift | An undocumented endpoint is added for convenience | Deny-by-default capability registry, allowlisted exact operations, source-register review, compliance tests and ADR |
| Gate bypass | A controller, job, UI path, or connector calls an external provider directly | Every external application use case depends on the owning `CapabilityPolicy` port; no UI authority; exact-operation tests; architecture review before adding a connector |
| Forged or stale evidence | An allow survives after its permission expires or is revoked | Effective periods, closed permission statuses, exact source versions, `require_review` as non-executable, transition tests for missing/unknown/expired/revoked/conflicting evidence |
| Emergency-control failure | A compromised source continues executing during an incident | Environment switches for imports, rulesets, AI, all egress and external links plus the global and persisted source/capability switches; every active switch overrides an allow; funding is code-disabled |
| Evidence-admin takeover | An attacker changes evidence or disables a kill switch | Environment-only high-entropy admin token, constant-time comparison, 404 fail-closed response, CSRF protection, rate limiting, no token in database/logs, deployment secret rotation |
| Audit data leakage | Raw builds, credentials, prompts, or personal data are written with decisions | Audit only source/version/capability/operation/outcome/reason/evidence IDs/non-secret condition names/time/actor type; tests reject token persistence |
| Public explanation overexposure | A read-only policy page reveals admin notes or secrets | Dedicated bounded response fields, no reviewer notes/tokens/audit context, read-only route, caching and rate limits |
| Deletion-token disclosure | A storage deletion token is recovered from the database, cache, or logs | Derive a 256-bit capability with the application key and owner-scoped high-entropy idempotency key, store only SHA-256, use constant-time verification, no-store responses, never log or include it in policy audits |
| Duplicate persistence | Client retry creates multiple encrypted records or changes a prior result | Required owner-scoped idempotency key, unique keyed hash, exact input/checksum/game/parser match, deterministic replay, conflict on key reuse |
| Parser format drift | A new upstream envelope/root is silently interpreted using old assumptions | Pinned source commits and license hashes, expiring evidence, exact root detection, parser versions, beta labelling, fail-closed unsupported structures |

## Privacy and retention baseline

- Collect only the content the user explicitly submits and the minimum operational metadata.
- Raw PoB share codes and XML remain memory-only on the synchronous format
  endpoint. The authenticated or anonymous privacy-session queued workflow stores only an explicitly
  consented, application-key-encrypted private object for worker handoff,
  deletes it immediately after parse or terminal rejection, and enforces a
  one-hour expiry ceiling if no worker completes it.
- Anonymous privacy-session secrets have 256 bits of randomness, are stored only
  as SHA-256 hashes, expire within 168 hours, and carry no stored IP/user-agent
  identity. Account and privacy-session analyses share the same owner-scoped
  authorization and deletion checks.
- Consented normalized PoB imports are application-key encrypted, default to 24 hours, cannot exceed the configured 168-hour ceiling, have owner-scoped idempotency and hash-only deletion capabilities, and are pruned hourly after expiry.
- Analysis history is authenticated, owner-HMAC scoped, encrypted, and
  user-deletable in the primary store, defaults to a 30-day live retention,
  and is covered by daily pruning. Production backup retention and
  restore-deletion procedures remain a release prerequisite.
- Do not use inputs or results to train models. Do not send them to AI unless the user enables the optional feature and the gate allows it.
- Optional provider requests use minimum typed context, `store: false`, no tools, hashed safety identifiers, and bounded tokens. OpenAI documents default abuse-monitoring retention of up to 30 days; Lootwright does not claim Zero Data Retention without approval.
- Logs use opaque correlation IDs and coarse metrics. No raw imports, full prompts, credentials, IP addresses beyond justified security retention, or protected game content.
- Backups inherit deletion and access controls; retention and restore-deletion behavior must be tested.

The authoritative schedule and restore-deletion sequence are in
[data retention](data-retention.md).

## Security verification before release

- Parser fuzzing and decompression-limit tests.
- Static analysis, dependency audits, secret scanning, and production configuration review.
- Authorization, optional verification, CSRF, CSP/XSS, unsafe redirect, SSRF,
  SQL injection, rate-limit, queue poisoning/replay, deletion, redaction, and
  egress-denial tests.
- Ruleset checksum/provenance and cross-game isolation tests.
- Table-driven default-policy and evidence-transition tests, exact-operation
  denial tests, all kill-switch scopes, admin-boundary tests, and audit-field
  review.
- AI-off, AI-timeout, prompt-injection, invalid-schema, and policy-denial tests.
- AI refusal, rate-limit, billing-limit, budget exhaustion, single-repair, unknown-reference, secret-redaction, deterministic-snapshot, and manual-smoke refusal tests.
- Manual review proving there is no GGG credential field, client/browser integration, scraper, undocumented endpoint, or funding entitlement.

## Residual and unresolved risks

- Format-only PoB1 and beta PoB2 interoperability are approved only for the pinned records through 2026-11-12; upstream drift or expired evidence disables execution.
- No public Lootwright account/login flow exists yet. Expiring anonymous
  privacy sessions permit owner-scoped persistence without a tracking identity;
  production UX must communicate credential loss, expiry, and deletion clearly.
- The primary-store deletion workflow is implemented, but backup-provider
  purge guarantees and restore-time deletion replay remain unresolved until a
  production backup system is selected.
- The local Compose baseline now uses loopback listeners, Redis authentication,
  and a non-superuser PostgreSQL application role. Production migrator/runtime
  separation, TLS verification, ACLs, and quarterly restore evidence remain
  deployment gates.
- Public hosting jurisdiction, privacy notice, retention periods, age policy, and incident-response contacts are undecided.
- Third-party ruleset data may not be licensable for redistribution even if technically accessible.
- The precise boundary between factual compatibility text and protected GGG expression needs legal review.
