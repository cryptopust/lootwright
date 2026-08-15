# Delivery Progress: Prompts 00–15

This is the controlled implementation sequence. A later prompt may refine earlier documentation but may not bypass its gates. `Pending` means no application implementation has been authorized or completed.

## Prompt 00 — Engineering constitution

- Status: Complete on 2026-08-14.
- Scope: inspect the workspace; read current official GGG Developer Docs, API Reference, and Terms; establish product, architecture, security, compliance, source, funding, license, and ADR policy.
- Evidence: the workspace was empty, was not a Git repository, and contained no generic ARPG/Demo ARPG foundation or unrelated project. Available tools were PHP 8.4.24, Composer 2.10.2, Node 24.14.1, npm 11.12.1, Git 2.55.0, Corepack 0.34.6, PHP PostgreSQL and Redis extensions; pnpm, Yarn, Bun, `psql`, Redis server/CLI, and Docker were not available on PATH.
- Gate: documentation and local-link validation pass; no application is implemented.

## Prompt 01 — Reproducible Laravel foundation

- Status: Complete on 2026-08-14.
- Scope delivered: scaffolded the application in the repository root from the
  official Laravel Vue starter-kit conventions; added PostgreSQL, Redis,
  Horizon, Docker Compose, health boundaries, CI-quality scripts, PHPUnit and
  Vitest foundations, and an original Lootwright shell. No parser, game data,
  AI provider, funding, API, Trade, or ingestion capability was added.
- Official baseline reviewed: Laravel 13 documentation commit
  `4030a84b979f7420788dda14df439dd4d66d765d` and official Vue starter-kit
  commit `0a2147e7cdee860a99fe9a97a176c0bd414d8189`. The starter conventions use
  Inertia 3, Vue 3 Composition API, TypeScript, Tailwind 4, shadcn-vue, and
  Vite.
- Locked runtime choices: PHP 8.4 baseline; Laravel `13.25.0`, Inertia Laravel
  `3.3.1`, Horizon `5.48.0`, `@inertiajs/vue3` `3.6.1`, Vue `3.5.41`,
  Tailwind CSS `4.3.3`, Vite `8.2.1`, and Vitest `4.1.10`. The local verification
  toolchain was PHP `8.4.24`, Composer `2.10.2`, Node `24.14.1`, and npm
  `11.12.1`.
- Testing choice: PHPUnit 12 is the sole PHP test style selected by the blank
  Vue starter kit. Vitest, Vue Test Utils, and happy-dom provide component/unit
  coverage without adding Playwright or another browser-test stack.
- Infrastructure: `compose.yaml` defines only the application, PostgreSQL 18,
  and Redis 8; every service has a health check and state uses named volumes.
  Database and Redis configuration is environment-driven. Horizon runs from
  the same application artifact and its dashboard is denied outside local
  development.
- Operations: public `GET /up` returns only plain-text `OK`. Token-protected
  `GET /ready` checks PostgreSQL and Redis, returns bounded statuses without
  exception details, and fails closed. The readiness token is accepted only in
  the `X-Lootwright-Readiness-Token` header.
- User interface: the neutral evidence-ledger shell uses only the original
  Obsidian, Bone, Ember, and Arcane palette, local system font stacks, CSS/SVG
  geometry, and vendored shadcn-vue source. It contains no publisher asset or
  remote runtime font/script and visibly includes the exact GGG notice.
- Gate evidence: Composer metadata/audit/format/static analysis/PHPUnit and npm
  clean install/audit/lint/typecheck/Vitest/build passed, as did both PHP and
  PowerShell documentation validators. PHPUnit ran 6 tests with 27 assertions;
  Vitest ran 2 tests. npm and Composer reported no vulnerability advisories.
- Setup verification: dependency installation, environment/key generation,
  frontend build, application feature tests, the plain-text liveness response,
  protected-readiness denial, and Compose YAML structure were verified locally.
  Docker and WSL2 are not installed on the verification host, so container
  image build/start and live PostgreSQL/Redis migrations could not be executed;
  this limitation is recorded rather than represented as a passing Docker run.
  The in-app browser was also unavailable, so screenshot-based visual QA remains
  for an environment with that browser surface.
- Gate: exact runtime/package choices are recorded, clean installs are locked,
  the GGG notice is visible in the base shell, and no game logic exists in
  Laravel controllers or components.

## Prompt 02 — Module and dependency skeleton

- Status: Complete on 2026-08-14.
- Scope delivered: added framework-independent Shared, Build Intake, PoE
  Catalog, Versioned Rulesets, Build Analysis, Upgrade Planning, Trade Recipe,
  Policy and Provenance, Usage and Funding domain namespaces plus provider-neutral
  application DTOs and AI gateway ports under `src/`.
- Identity and isolation: `GameEdition` is closed to `poe1`/`poe2`; platform
  realms are edition-validated; every catalog identifier and version is
  edition-scoped; `BuildId`, `AnalysisId`, and `RulesetId` consistently validate
  caller-supplied UUIDv7 values. Canonical builds reject edition, patch, league,
  and parser mismatches before analysis.
- Invariants: immutable objects use private constructors and named factories
  returning typed domain errors. Budget serialization uses normalized decimal
  strings rather than floats. Canonical JSON is byte-stable, associative keys
  are sorted, and serialized edition values cannot be silently reinterpreted.
- Ports and DTOs: ports live in the context that owns the capability. Optional
  AI ports and command/query DTOs are application-layer types; no provider SDK,
  Laravel type, or domain authority was introduced.
- Compliance: capability and provenance types remain contracts only and default
  absent capabilities to denial. Funding is structurally disabled. Manual
  filters reject URLs and API-shaped payload text. No dataset, parser, formula,
  external endpoint, Trade identifier, price source, or provider adapter was
  added.
- Architecture enforcement: tests scan every file under `src/`, reject Laravel,
  Illuminate, Symfony HTTP, Inertia, OpenAI, Guzzle HTTP, `App`, and other
  outward imports, enforce the documented module dependency matrix, and prevent
  future PoE1/PoE2 adapter cross-imports.
- Test evidence: pure PHPUnit tests cover constructor/factory invariants,
  equality, UUID strategy, canonical serialization, invalid identifiers and
  checksums, low-confidence clarification, deny-by-default permissions, funding
  status, evidence traces, ruleset mismatch, patch mismatch, parser mismatch,
  and cross-edition rejection. No real game fact is present in fixtures.
- Gate evidence: `composer validate --strict`, `composer audit`, Pint format
  checking, PHPStan level 7, PHPUnit, `npm ci`, the high-severity npm audit,
  ESLint, Vue TypeScript checking, Vitest, the Vite production build, and the
  PowerShell documentation validator all passed. PHPUnit ran 186 tests with
  2,776 assertions; Vitest ran 2 tests across 2 files; Composer and npm reported
  no vulnerability advisories. Documentation validation covered 25 Markdown
  files, including required files, local links, fences, headings, prompt states,
  and the non-affiliation notice.
- Gate: forbidden framework-to-domain direction and PoE1-to-PoE2 contamination
  are prevented by automated tests; namespace ownership and dependency direction
  are documented in [domain foundation](architecture/domain-foundation.md).

## Prompt 03 — Policy and Provenance Gate

- Status: Complete on 2026-08-14.
- Official-policy re-review: the exact GGG Developer Docs, API Reference, and
  Terms URLs returned HTTP 200 when retrieved again at 13:16 UTC. No material
  policy-text change was found: registration remains unavailable, the
  documented-resource boundary and non-affiliation requirement remain, the
  internal Trade paths remain absent from the API Reference, and the privacy
  notice still says last updated October 2024. No capability was broadened.
- Scope delivered: implemented pure domain concepts for data sources and
  versions, source/access types, exact capabilities and operations, permission
  evidence and URLs, retrieval/effective periods, attribution, closed evidence
  statuses, policy decisions/reasons/versions, kill switches, and a fail-closed
  evaluator. Laravel owns only the persistence, seed, audit, HTTP, and adapter
  concerns under `app/Modules/PolicyProvenance`.
- Defaults: seeded 15 sources, 15 versions, 16 evidence records, 58 exact
  operation rules, and an inactive emergency global switch. User-pasted input
  requires explicit submission and storage consent for persistence. GGG APIs,
  internal Trade, credentials, scraping, client/browser interaction, remote
  pobb.in, PoB Community, RePoE, protected assets, OpenAI, donations, and
  monetized hosting use the conservative decisions in the [capability
  matrix](compliance/capability-matrix.md). No external connector was added.
- Execution and audit: only `allow` is executable; `require_review` remains
  non-executable. Exact source/version/capability/operation matching, current
  allowed evidence, trusted condition names, and clear kill switches are all
  required. Every database-backed request writes a UUIDv7 decision audit with
  no raw input, secret, prompt, or unnecessary personal data. Database failure
  denies execution.
- Operations: `POLICY_GLOBAL_KILL_SWITCH` provides immediate environment-level
  disablement; persisted switches cover global, source, capability, and
  source-capability scopes. Evidence and persisted-switch management are
  protected by an environment-only admin token, CSRF, rate limits, and a 404
  fail-closed boundary. Users can read bounded source explanations without
  reviewer metadata or mutation authority.
- Migration evidence: `php artisan migrate:fresh --seed --force` passed against
  an isolated SQLite verification database and produced the expected 15 source,
  15 version, 16 evidence, 58 rule, and 1 kill-switch rows. Production remains
  configured for PostgreSQL through environment variables.
- Gate evidence: Composer validation/audit, Pint, PHPStan level 7, PHPUnit,
  clean npm install/high-severity audit, ESLint, Vue TypeScript, Vitest, Vite,
  and the PowerShell documentation validator passed. PHPUnit ran 292 tests with
  3,458 assertions; Vitest ran 2 tests across 2 files; Composer and npm reported
  no vulnerability advisories; documentation validation covered 26 Markdown
  files. Table-driven tests exercise every seeded default, every evidence
  status transition, exact-operation denial, consent, all kill-switch scopes,
  audit persistence, admin protection, and public explanation boundaries.
- Gate: every future external adapter remains unreachable without an active
  exact allow decision; undocumented GGG endpoints are explicitly denied and
  absent from connector code.

## Prompt 04 — Versioned ruleset catalog

- Status: Pending.
- Scope: implement immutable ruleset identities, import staging, checksum verification, review/activation, supersession, and exact game/patch/league resolution without adding unapproved game data.
- Gate: tampering, ambiguous version, game mismatch, expired provenance, and parser incompatibility fail closed.

## Prompt 05 — PoE1 PoB import

- Status: Complete on 2026-08-14 for format-only pre-ruleset intake.
- Source and license review: pinned Path of Building Community PoE1 commit
  `bcbca9b60b04abc17935c84ff3589342193bd758` and PoE2 commit
  `5d173cbf8c9cf394a975cbb813f19d0b6dc67ea6`; recorded root-license SHA-256
  values and attribution. Lootwright uses an independent PHP implementation
  and copies no upstream Lua, dependency, dataset, formula, asset, or build.
- Scope delivered: strict Base64/Base64URL and zlib decoding, bounded hardened
  XML, exact root-based edition evidence, separate PoE1 and beta PoE2
  parser/normalizers, warnings, unsupported-field records, parser/source
  provenance, checksums, and a deterministic `CanonicalImportedBuild`. Unknown
  patches are not promoted to analysis-grade `CanonicalBuild` and no ruleset,
  game data, or formula was added.
- Delivery and privacy: pasted code/XML and uploaded plain-text code use the
  deny-by-default gate before processing and the exact pinned format gate before
  normalization. No URL is fetched. Raw input is never persisted or logged;
  authenticated, consented normalized JSON is encrypted for 24 hours by default
  (168-hour maximum), has hash-only token deletion, and is pruned hourly.
  Imported prose is untrusted text and is not sent to OpenAI.
- Principal-engineer review on 2026-08-14 confirmed and corrected: edition-free
  parser-local IDs; PoE1 choice leakage into PoE2; partial skill/gem truncation;
  silent invalid scalar coercion; missing parser-time enforcement; a concrete
  adapter dependency under provider-neutral `src/Application`; anonymous,
  non-idempotent persistence; owner/idempotency metadata exposure risk; a local
  CLI stream-wrapper/UNC network path; missing local-policy effective-start and
  global-kill-switch enforcement; cacheable private responses; and a privacy
  regression test that had not actually inserted its sentinel text. Regression
  tests now lock each boundary.
- Persistence hardening: `persistent_store` now additionally requires the
  `authenticated_user` Policy Gate condition, a high-entropy idempotency key,
  keyed owner/idempotency hashes, exact input/checksum/game/parser replay, and
  no-store responses. Different-owner keys are isolated; conflicting reuse
  fails. Lootwright sessions are encrypted and remain unrelated to GGG. No
  public account/login flow exists yet, so hosted persistence remains disabled
  for anonymous users while transient import remains available.
- Verification: original tiny fixtures and tests cover PoE1, beta PoE2,
  ambiguous edition, URLs, malformed Base64/compression/XML/UTF-8, expansion
  bombs, XXE, depth/size/node/skill/gem/time limits, unknown and invalid scalar
  data, duplicate items, edition-scoped identifiers, deterministic round trips,
  upload validation, all import-stage policy denials, authenticated
  owner-scoped idempotency, encryption, retention, deletion, no-store private
  responses, redacted logs, and database-free CLI execution without URL,
  stream-wrapper, or UNC access.
- Migration and gate evidence: an isolated in-memory SQLite
  `migrate:fresh --seed --force` completed with 15 sources, 15 versions, 16
  evidence records, 58 exact rules, the inactive global switch, and the empty
  PoB-import table. Composer validation/audit, Pint, PHPStan level 7, PHPUnit,
  npm clean install/high-severity audit, ESLint, Vue TypeScript, Vitest, Vite,
  and the PowerShell documentation validator passed. PHPUnit ran 351 tests with
  4,213 assertions; Vitest ran 2 tests across 2 files; Composer and npm reported
  no vulnerability advisories; documentation validation covered 29 Markdown
  files. The README fixture command and both HTTP routes were also verified
  locally.
- Compatibility: see [PoB import compatibility](compatibility/pob-import.md),
  [ADR 0010](adr/0010-format-only-pob-import.md), and the
  [attribution record](compliance/path-of-building-attribution.md).

## Prompt 06 — PoE1 item-text import

- Status: Pending.
- Scope: parse explicitly pasted PoE1 item text conservatively into evidence facts without building a scraped corpus or reproducing unnecessary protected expression.
- Gate: hostile markup, extreme input, ambiguous locale/version, unsupported fields, and cross-game text produce safe typed outcomes.

## Prompt 07 — Deterministic PoE1 analysis vertical slice

- Status: Pending and blocked on approved PoE1 ruleset sources.
- Scope: choose a narrow set of high-value analyses; implement pure calculations, explicit precision/rounding, evidence, certainty, and limitations.
- Gate: unit, fixture, property/boundary, mutation, determinism, and historical ruleset tests pass with AI and network disabled.

## Prompt 08 — Findings experience

- Status: Pending.
- Scope: present deterministic findings, severity, certainty, evidence, ruleset identity, unsupported facts, and actionable explanations in the Inertia/Vue UI.
- Gate: accessibility, responsive behavior, XSS protection, empty/error/loading states, and no client-side authoritative recalculation.

## Prompt 09 — Prioritized upgrade recommendations

- Status: Pending.
- Scope: implement deterministic objectives, constraints, scoring, tie-breaks, alternatives, and rationale without market prices.
- Gate: ranking is reproducible, evidence-linked, transparent about unknown cost/availability, and robust to conflicting goals.

## Prompt 10 — Manual Trade-filter recipes

- Status: In progress as of 2026-08-14; the deterministic compiler and safety
  boundary are implemented with fixture-only vocabulary, while production
  output remains blocked on Prompt 04, Prompt 07, and Prompt 09.
- Delivered: framework-independent application DTOs, checksum-bound approved
  vocabulary, exact decimal ranges, strict and broad variants, structural
  budget relaxation, automatic conflict exclusions, slot dependencies,
  finding-to-filter traces, ruleset/source/confidence metadata, canonical JSON,
  and a URL-free plain-text renderer.
- Edition policy: PoE1 compilation is available only when an approved exact
  vocabulary is supplied. PoE2 has a separate adapter that fails closed until
  phase two is activated.
- Unknown mappings: unsupported modifier, target, constraint, influence,
  fracture, rarity, corruption, or open-affix concepts are emitted as
  unresolved requirements with clarification questions; nothing is guessed.
- Policy: local manual generation and one explicit generic homepage link have
  exact allows. Live listing fetch and encoded Trade URL generation have exact
  denials. No Trade HTTP, browser, clipboard, automation, or price capability
  was added.
- Tests: UI-independent canonical serialization, exact labels/values,
  PoE1/PoE2 isolation, conflicts, budget relaxation, unsupported constraints,
  and Policy Gate live-fetch/encoded-link denials are covered with fixture-only
  facts.
- Scope: transform recommendations into descriptive item categories, ranges, priorities, alternatives, and relaxation order for manual entry.
- Gate: output has no official Trade API IDs, query payloads, generated search
  links, prices, listings, browser behavior, or undocumented endpoints. One
  generic query-free official homepage link is the sole allowed link-out.

## Prompt 11 — Optional provider-neutral AI

- Status: Implementation complete on 2026-08-15; production execution remains blocked on an explicit provider `allow`, privacy/opt-in UX review, and deployment hard spend-limit verification.
- Scope delivered: provider-neutral strict DTOs and schemas for BuildIntent candidates, clarification sets, and Turkish/English explanation bundles; deterministic-first parsing; exact ruleset-term and deterministic-reference validation; safe template fallbacks; one bounded schema repair; privacy-permitted normalized caching; and an OpenAI Responses API adapter using Laravel HTTP with no provider SDK dependency.
- Safety and privacy: `OPENAI_ENABLED=false` by default; every request is opt-in, exact-operation Policy Gate authorized, tool-free, `store: false`, token-bounded, and minimum-context. Raw prompts, responses, secrets, PoB, and personal identifiers are excluded from local audits. Provider refusal, timeout, malformed/schema-invalid output, unknown terms, prompt injection, unsafe prose, policy denial, or disabled configuration gracefully falls back.
- Cost and operations: PostgreSQL transactionally reserves and settles per-user, per-IP, global daily, and global monthly integer micro-USD budgets. Billing/quota failures are terminal; transient failures alone receive bounded jittered retry. A one-request synthetic smoke command requires explicit confirmation and a caller-supplied cap.
- Test evidence: fake transports cover valid, malformed, refused, timed-out, temporary rate-limited, billing-limited, schema-invalid, unknown-reference, prompt-injection, budget-exceeded, disabled, low-confidence clarification, strict payload, and deterministic recommendation snapshot cases. The real seeded Policy Gate test proves OpenAI remains `require_review` and non-executable.

## Prompt 12 — Persistence, privacy, and workspace lifecycle

- Status: In progress as of 2026-08-14; primary-store orchestration and
  lifecycle are implemented, while production backup deletion and public
  account UX remain release prerequisites.
- Application workflow: added framework-neutral `SubmitBuildArtifact`,
  `ParseAndNormalizeBuild`, `RequestClarification`, `CreateAnalysis`,
  `RunDeterministicAnalysis`, `RetrieveAnalysis`,
  `CompareAnalysisVersions`, `ReanalyzeWithGoalsOrBudget`,
  `ExplainPolicyDecision`, and `DeleteUserData` use cases with typed ports and
  no Laravel imports under `src/`.
- Persistence and queues: added owner-scoped PostgreSQL repositories,
  encrypted immutable snapshots and hashes, encrypted private raw-artifact
  handoff with a one-hour ceiling, atomic/idempotent state claims, after-commit
  events, Redis/Horizon routing, three-attempt transient-only backoff, and
  terminal invalid/policy outcomes.
- Privacy and authorization: analysis routes require authentication, owner
  mismatches return not found, raw text stays out of database JSON/queue
  payloads/logs, deletion spans Analysis and earlier Build Intake persistence
  through typed ports, and only unlinkable aggregate deletion counts remain.
- Fail-closed boundary: the production deterministic engine reports an exact
  ruleset as unavailable until Prompt 04 and Prompt 07 approve and implement
  one. Full completion in tests uses deterministic and Policy Gate fakes; AI
  fakes verify the workflow makes no provider calls.
- Test coverage: feature/integration tests exercise all visible states,
  immutable hashes, duplicate submission replay/conflict, transaction rollback,
  transient retry, terminal invalid/policy denial, clarification, authorization,
  reanalysis/comparison, expiry pruning, deletion isolation, and audited policy
  explanation.
- Scope: implement PostgreSQL repositories, short-lived raw imports, normalized/result retention, deletion, authorization, encryption, and Redis/Horizon jobs.
- Gate: tenant/workspace isolation, idempotency, backup/deletion behavior, log redaction, queue limits, and retention UX are verified.

## Prompt 13 — Production UX and operations

- Status: Pending.
- Scope: complete onboarding, analysis history where approved, accessible responsive UI, Horizon supervision, health/readiness, safe telemetry, deployment configuration, and incident runbooks.
- Gate: core workflow remains useful without AI/external integrations and exposes the required GGG notice visibly.

## Prompt 14 — PoE1 release assurance

- Status: Pending.
- Scope: security review, compliance audit, source/license review, performance budgets, browser/accessibility QA, deterministic regression suite, disaster recovery, and public documentation.
- Gate: all commands in `AGENTS.md` pass; no unresolved release-blocking policy, privacy, licensing, or provenance issue remains.

## Prompt 15 — PoE2 phase-two adapter

- Status: Pending and not authorized until PoE1 release gates pass.
- Format-only exception: a separately namespaced beta PoB2 intake adapter may
  produce `CanonicalImportedBuild` for compatibility testing. It does not
  activate PoE2 rulesets, analysis, UI claims, or game datasets.
- Scope: approve PoB2/ruleset sources, create an isolated PoE2 adapter, add PoE2 UI/persistence support, and run shared-port plus cross-game negative tests.
- Gate: no PoE1 identifiers/rules leak into PoE2, no speculative mappings ship, and a new ADR explicitly activates PoE2.

## Change discipline

Each prompt ends by updating this file with status, decisions, commands, evidence, and unresolved risks. A prompt blocked by policy or provenance stays blocked; implementation convenience is not a reason to advance it.
