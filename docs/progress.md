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

- Status: Fixture-backed UI implementation complete on 2026-08-15; production
  result binding remains blocked on Prompt 04 and Prompt 07.
- Delivered: responsive findings groups, severity/category labels, explicit
  confidence, deterministic evidence disclosures, source/ruleset identity,
  limitations, unresolved/partial states, and keyboard-native `Why?` controls.
  Vue consumes typed presentation data and performs no authoritative
  recalculation.
- Safety: every demo surface is labelled fixture-only, PoE2 is inactive beyond
  format review, AI wording is separated from calculations, and no protected
  publisher asset or copied game interface is present.
- Verification: Vitest covers evidence disclosure and edition identity;
  Playwright covers the critical findings path and responsive fixture visuals.
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

- Status: Application and primary-store implementation complete on 2026-08-15;
  production backup deletion and public account/session UX remain release
  prerequisites.
- Application workflow: added framework-neutral `SubmitBuildArtifact`,
  `ParseAndNormalizeBuild`, `RequestClarification`, `CreateAnalysis`,
  `RunDeterministicAnalysis`, `RetrieveAnalysis`,
  `CompareAnalysisVersions`, `ReanalyzeWithGoalsOrBudget`,
  `ExplainPolicyDecision`, `DeleteUserData`, `DeleteBuild`, portable export,
  provenance retrieval, BuildIntent resolution, prioritized-upgrade creation,
  manual-recipe orchestration, and constrained explanation persistence use
  cases with typed ports and no Laravel imports under `src/`.
- Persistence and queues: added owner-scoped PostgreSQL repositories,
  encrypted immutable snapshots and hashes, relational encrypted
  finding/recommendation/recipe projections, provenance/policy references,
  encrypted private raw-artifact handoff with a one-hour ceiling, optimistic
  completion, and a narrow transactional outbox. Parse/analysis jobs carry
  edition and selected ruleset checksum, route to isolated Horizon queues, and
  use bounded transient-only retry; stale rulesets, invalid input, and policy
  outcomes are terminal.
- Privacy and authorization: account principals and expiring anonymous
  privacy-session principals are owner isolated; anonymous secrets are stored
  only as hashes with no IP/user-agent identity. Raw text stays out of database
  JSON, queue payloads, and logs. Build or full-principal deletion cascades
  snapshots/products and earlier Build Intake or AI metadata through typed
  ports; only unlinkable aggregate deletion counts remain.
- Fail-closed boundary: the production deterministic engine reports an exact
  ruleset as unavailable until Prompt 04 and Prompt 07 approve and implement
  one. Full completion in tests uses deterministic and Policy Gate fakes; AI
  fakes verify the workflow makes no provider calls.
- Export and observability: owner-scoped endpoints expose exact provenance and
  policy state. Portable schema `1.0.0` is canonical, timestamp-free, and
  contains hash-verified deterministic input/output plus findings,
  recommendations, recipes, selection, and source/ruleset references.
- Test coverage: feature/integration tests exercise all visible states,
  immutable hashes, concurrent replay/conflict, transaction rollback, partial
  product failure, optimistic duplicate completion, outbox retry/recovery,
  sync-queue execution, stale edition/ruleset jobs, clarification,
  account/anonymous authorization, export/provenance isolation, reanalysis,
  deletion cascades, AI-disabled fallback, and constrained explanation
  persistence. Application tests cover deterministic prioritization and typed
  BuildIntent/clarification resolution.
- Migration and gate evidence: `migrate:fresh --seed --force` completed against
  the isolated SQLite `:memory:` test profile, including the application
  persistence migration and policy seed. Composer validation/audit, Pint,
  PHPStan, PHPUnit, clean npm install/high-severity audit, ESLint, Vue
  TypeScript, Vitest, Vite, and documentation validation passed. PHPUnit ran
  518 tests with 7,385 assertions; Vitest ran 2 tests across 2 files; Composer
  and npm reported no vulnerability advisories; documentation validation
  covered 36 Markdown files. A local PostgreSQL service and Docker were not
  available, so PostgreSQL-driver execution remains a CI/deployment check.
- Scope: implement PostgreSQL repositories, short-lived raw imports, normalized/result retention, deletion, authorization, encryption, and Redis/Horizon jobs.
- Gate: tenant/workspace isolation, idempotency, backup/deletion behavior, log redaction, queue limits, and retention UX are verified.

## Prompt 13 — Production UX and operations

- Status: UX implementation complete with fixture-only data on 2026-08-15;
  production data binding and remaining deployment operations stay pending.
- Delivered: original Lootwright landing page, four-step input/privacy wizard,
  import review, build overview, findings, prioritized upgrades, Manual Trade
  Recipes, provenance/policy status, operational states, privacy/deletion,
  methodology, limitations, non-affiliation, personal AI usage, and
  funding-disabled pages. Turkish defaults with an English localization
  foundation.
- Accessibility and responsive evidence: semantic landmarks, skip navigation,
  visible focus, keyboard controls, live validation/copy feedback,
  reduced-motion support, text-plus-color states, and mobile layouts from 320
  pixels are implemented. Chromium visual references and horizontal-overflow
  checks cover 390 by 844, 768 by 1024, and 1440 by 1000 pixels.
- Policy evidence: the recipe UI copies only URL-free Lootwright text after an
  explicit click and exposes exactly one query-free official PoE1 Trade
  homepage link. The funding page has no donation, payment, sponsor, waitlist,
  or contact action. The exact non-affiliation notice is persistent.
- Test evidence: Vitest runs 13 component tests across 8 files; Playwright runs
  7 fake-data Chromium tests including the wizard, evidence, manual Trade,
  localization, and three responsive visual comparisons. See
  [interface workflows](product/interface-workflows.md).
- Gate evidence: Composer validation/audit, Pint, PHPStan, PHPUnit, clean npm
  install/high-severity audit, ESLint, Vue TypeScript, Vitest, Chromium browser
  tests, Vite production build, and documentation validation passed. PHPUnit
  ran 519 tests with 7,505 assertions; Vitest ran 13 tests; Playwright ran 7
  tests; Composer and npm reported no vulnerability advisories; documentation
  validation covered 39 Markdown files.
- Scope: complete onboarding, analysis history where approved, accessible responsive UI, Horizon supervision, health/readiness, safe telemetry, deployment configuration, and incident runbooks.
- Gate: core workflow remains useful without AI/external integrations and exposes the required GGG notice visibly.

## Prompt 14 — PoE1 release assurance

- Status: Security hardening baseline implemented on 2026-08-15; public release
  remains pending production contacts, jurisdiction/legal review, selected
  backup-provider evidence, isolated restore exercise, and approved rulesets.
- Delivered: default-off admin boundary and optional account verification,
  production security headers/CSP, named HMAC-keyed rate limits, strict parser
  and escaped-rendering regression coverage, exact redirect-disabled public-DNS
  egress guard, recursive secret/log redaction, queue identity rejection,
  independent import/ruleset/AI/egress/link switches, 30-day application/AI
  retention pruning, Redis authentication, and a non-superuser local PostgreSQL
  application role.
- Operations: added the [security baseline](security/security-baseline.md),
  [incident response](security/incident-response.md),
  [data retention](security/data-retention.md), and
  [deployment checklist](security/deployment-security-checklist.md).
- Attack-test evidence: horizontal and vertical authorization, optional email
  verification, hostile parser inputs, escaped rendering, redirect refusal,
  rate limits, policy and emergency-switch bypass attempts, public-DNS egress
  enforcement, malformed queue payloads, retention pruning, and recursive
  secret redaction are covered by the automated suites. PHPUnit ran 533 tests
  with 7,600 assertions, Vitest ran 15 tests across 8 files, and Playwright ran
  7 Chromium tests including CSP-sensitive navigation and responsive fixtures.
- Gate evidence: Composer validation, Composer audit, Pint, PHPStan, PHPUnit,
  clean npm install, high-severity npm audit, ESLint, Vue TypeScript, Vitest,
  Playwright, Vite production build, and documentation validation all passed.
  Composer and npm reported no known vulnerability advisories; documentation
  validation covered 43 Markdown files. Docker was unavailable locally, so
  live PostgreSQL/Redis Compose validation and the isolated backup-restore
  exercise remain explicit deployment checks and were not claimed as passed.
- Scope: security review, compliance audit, source/license review, performance budgets, browser/accessibility QA, deterministic regression suite, disaster recovery, and public documentation.
- Gate: all commands in `AGENTS.md` pass; no unresolved release-blocking policy, privacy, licensing, or provenance issue remains.

## Prompt 15 — Conservative funding architecture and request package

- Status: informational architecture complete on 2026-08-15; funding,
  monetization, payment acceptance, and provider links remain disabled.
- Evidence: the current GGG Terms response was retrieved at 20:26 UTC (HTTP
  200; SHA-256
  `8acc7ccf100a595b499d949cab01bba429f60f265ae53177a41c6e760588f77b`)
  and retained the summarized personal/non-commercial and prior-written-approval
  restrictions. No actual GGG support correspondence or written funding
  permission exists in the repository. Official OpenAI pricing and Codex for
  Open Source program/terms were reviewed; application does not guarantee
  eligibility, selection, sponsorship, access, or API credits.
- Architecture: `FUNDING_ENABLED=false` is an operator request rather than an
  authorization. Exact dated decision/evidence identifiers, explicit operator
  acknowledgement, a versioned disclosure, permanent equality conditions, and
  an executable `monetized_hosting` Policy Gate allow are also required. The
  seeded rule/evidence explicitly denies activation and no payment adapter
  exists, so `accepting_funds` is always false.
- Isolation: low/base/high monthly projections use configuration-only hosting,
  traffic, token, and dated OpenAI pricing assumptions. They read and write no
  player, build, account, analysis, or supporter data. Analysis/reanalysis
  schemas prohibit donor, badge, funding-tier, and sponsor-rank state before
  product output or queue work can be created.
- UI: the non-transactional funding page explains open-source/unaffiliated
  status, disabled policy, projected costs, future aggregate reporting, and the
  permanent no-advantage rule. It renders no payment, donation, advertising,
  affiliate, sponsorship, revenue-generating social, waitlist, or contact action.
- Request package: added the [one-pager](sponsorship/openai-one-pager.md),
  [technical architecture](sponsorship/technical-architecture.md),
  [responsible AI/evals](sponsorship/responsible-ai-and-evals.md),
  [token cost model](sponsorship/token-cost-model.md),
  [milestones](sponsorship/milestone-plan.md),
  [project impact](sponsorship/project-impact.md), and unsent
  [application email](sponsorship/application-email.md). Every document avoids
  approval, eligibility, free-credit, or endorsement claims.
- Gate evidence: Composer validation/audit, Pint, PHPStan, PHPUnit, clean npm
  install/high-severity audit, ESLint, Vue TypeScript, Vitest, Playwright, Vite
  production build, and documentation validation passed. PHPUnit ran 541 tests
  with 7,720 assertions; Vitest ran 15 tests across 8 files; Playwright ran 7
  Chromium tests; documentation validation covered 50 Markdown files. Composer
  and npm reported no known vulnerability advisories.
- Remaining blockers: an actual legal/policy approval, preserved primary GGG
  permission if required, OpenAI selection/award evidence, entity/tax/accounting
  review, and a separately reviewed payment design. Their absence keeps funding
  off and is not an implementation failure.

## Reproducible evaluation system

- Status: implemented on 2026-08-16 with default-off live-provider execution;
  production game-accuracy evaluation remains blocked until reviewed production
  rulesets and the deterministic engine exist.
- Coverage: 31 fast structural cases cover PoE1/PoE2 import, ambiguity/mismatch,
  incomplete and hostile inputs, intent categories/goals/budgets in Turkish and
  English, conflicting constraints, stale/cross-edition rulesets, AI failure modes,
  reviewed synthetic findings, and Manual Trade broadening/refusal/unresolved paths.
  The 35-case extended suite adds 50-run parser/deterministic replay and generated
  deep-XML/decompression-bomb failures.
- Gates: parser and safe-failure rates, edition precision, structural finding
  precision, unsupported disclosure, recommendation/Trade trace completeness, AI
  schema/ID resolution, and deterministic replay require 100%. Cross-edition output,
  undocumented network calls, and accepted hallucinated IDs require zero. Per-case
  latency/memory and estimated token/cost ceilings are enforced separately.
- Reproducibility: committed JSON Schema, original tiny fixtures, reviewed stable
  baselines, redacted JSON/Markdown reports, case/source fingerprints, CI fast-suite
  enforcement, and a documented non-rubber-stamp golden-review process are in place.
- Privacy and provider isolation: ignored private fixtures require explicit user
  authorization and may run only in the manual extended suite. Live OpenAI evaluation
  is never in normal CI and additionally requires explicit confirmation, configuration,
  secret, Policy Gate allow, local budget reservation, a hard operator cost cap, and
  redaction. No live provider evaluation was run for this change.
- Operations: see the [evaluation system](operations/evaluation-system.md).
- Gate evidence: fast (31 cases) and extended (35 cases) evals, Composer validation
  and audit, Pint, PHPStan, PHPUnit, clean npm install and high-severity audit, ESLint,
  Vue TypeScript, Vitest, Playwright, Vite production build, and documentation
  validation passed. PHPUnit ran 549 tests with 7,751 assertions; Vitest ran 15 tests;
  Playwright ran 7 Chromium tests; documentation validation covered 54 Markdown
  files. Composer and npm reported no known vulnerability advisories.

## CI/CD and production packaging

- Status: implementation complete on 2026-08-16 without publishing an image,
  uploading an artifact, creating infrastructure, registering a domain, or deploying.
- CI: lockfile-only Composer/npm installation, validation/audits, formatting/lint,
  PHPStan, full backend/frontend suites, separately visible architecture,
  parser-security and Policy Gate suites, fast eval, browser tests, production frontend
  build, documentation validation, and a PostgreSQL migration
  fresh/status/rollback/reapply cycle are required. A second job renders the production
  Compose definition, builds the image, proves its non-root/runtime-only contents, and
  runs the offline production configuration preflight; it has no push/deploy step.
- Release guardrails: a dependency-free scan covers committed and untracked candidate
  files and fails on credential-shaped content, secret-valued environment examples,
  alternate lockfiles, runtime GGG-session or undocumented Trade endpoint handling,
  protected binary assets/dataset payloads, payment dependencies/actions, or enabled
  funding/AI/egress defaults.
- Package: exact-version PHP/Node/Composer build stages produce one application image
  deployed only by OCI digest. Separate web (Nginx/PHP-FPM), Horizon, scheduler, and
  one-off migrator roles run as UID/GID 10001 with read-only root, dropped capabilities,
  explicit private artifact volume, verified PostgreSQL/Redis TLS, and secret injection.
  Web/worker startup never runs migrations.
- Operations: added the [deployment runbook](operations/deployment.md),
  [environment reference](operations/environment-reference.md),
  [backup/restore procedure](operations/backup-restore.md),
  [ruleset release runbook](operations/ruleset-release.md), and
  [GGG/OpenAI release checklist](operations/release-policy-checklist.md). Ruleset
  activation remains explicitly blocked because no approved production source or
  importer exists.
- Health/privacy: `/up` remains dependency-free; protected `/ready` checks only
  PostgreSQL/Redis. Horizon UI is local-only and default-off. Nginx/Laravel telemetry
  excludes bodies, queries, IP/user-agent/referrer, secrets, imports, prompts, and
  private notes. Retention/deletion and isolated restore-time pruning are documented.
- Local gate evidence: Composer validation/audit, Pint, PHPStan, PHPUnit, guardrails,
  fast eval, clean npm install/audit, ESLint, Vue TypeScript, Vitest, Playwright, Vite
  build, documentation validation, targeted architecture/parser/Policy suites, and an
  isolated SQLite migration fresh/rollback/reapply cycle passed. PHPUnit ran 553 tests
  with 7,777 assertions; Vitest ran 15 tests; Playwright ran 7 Chromium tests; docs
  validation covered 59 Markdown files; dependency audits reported no known
  vulnerabilities.
- Environment limitation: Docker, Bash, PostgreSQL client/server, and Redis were not
  installed locally, so no container was built and no PostgreSQL/Redis resource was
  created. The checked CI workflow performs Docker/Compose/shell and ephemeral
  PostgreSQL verification before merge. Production restore remains a release blocker
  until a provider is selected and the first recorded isolated exercise passes.

## Prompt 16 — PoE2 phase-two adapter

- Status: Pending and not authorized until PoE1 release gates pass.
- Format-only exception: a separately namespaced beta PoB2 intake adapter may
  produce `CanonicalImportedBuild` for compatibility testing. It does not
  activate PoE2 rulesets, analysis, UI claims, or game datasets.
- Scope: approve PoB2/ruleset sources, create an isolated PoE2 adapter, add PoE2 UI/persistence support, and run shared-port plus cross-game negative tests.
- Gate: no PoE1 identifiers/rules leak into PoE2, no speculative mappings ship, and a new ADR explicitly activates PoE2.

## Final principal-engineer MVP readiness review

- Status: **FAIL** on 2026-08-16; the repository is not production-ready. See
  [MVP readiness](release/mvp-readiness.md).
- Review scope: read the constitution, all documents/ADRs, complete local Git
  history and worktree; audited product/compliance/provenance/architecture,
  parser safety, deterministic integrity, Manual Trade, AI, security/privacy,
  funding, UX/accessibility, operations, and open-source boundaries.
- Acceptance evidence: added a real-parser, anonymous-session PoE1 workflow that
  completes against an explicitly fake deterministic engine/ruleset, persists a
  finding/recommendation plus trace-bearing strict/broad Manual Trade filters,
  makes no AI call, and fully deletes primary-store data. Strengthened the fake
  constrained-AI flow to prove the deterministic recommendation record is
  unchanged. Added a production-binding test proving exact ruleset/analyzer
  absence still fails closed.
- Fixed review defects: corrected stale README capability wording; added
  contribution, security-reporting, and third-party notice policies; made the
  test recipe materially assert exact filter labels, numeric ranges, relaxation,
  and finding trace.
- Final local gates: Composer validation/audit, Pint, PHPStan, 555 PHPUnit tests
  with 7,809 assertions, repository guardrails, 330
  architecture checks, 39 parser-security tests, 71 Policy Gate tests, 31 fast
  evals, 35 extended evals, clean npm install/audit, ESLint, Vue TypeScript, 15
  Vitest tests, 7 Playwright tests, Vite production build, and documentation
  validation across 63 Markdown files passed. SQLite migrations passed a
  fresh/status/rollback-last/reapply cycle. An isolated Windows/SQLite
  `setup:windows` archive install, full migration, build, and five foundation
  tests also passed.
- Critical blockers: no approved PoE1 ruleset source/catalog/activation and no
  production deterministic analyzer, finding formulas, prioritizer, or
  production recipe vocabulary. Shipping analysis UI remains fixture-backed.
- Deployment blockers: Docker/PostgreSQL/Redis production checks and restore
  exercise were unavailable locally; the final release commit still needs CI,
  staging, backup/deletion replay, named contacts, jurisdiction/privacy/legal
  decisions, and exact release-time GGG/OpenAI policy review.

## Public repository and Laravel Cloud documentation alignment

- Status: documentation and repository-maintenance alignment completed on
  2026-08-16; the GitHub repository represents a working **pre-alpha
  foundation**, not a completed end-user MVP or public production service.
- Evidence review: the implementation contains more than Prompts 00-02. Later
  commits provide bounded format-only PoB readers, application/persistence
  orchestration, Policy Gate enforcement, optional-AI infrastructure,
  fixture-only findings/upgrade/Manual Trade presentation, security controls,
  evaluations, and CI packaging. Those technical completions remain recorded in
  their historical entries and were not removed.
- Product boundary: no approved production PoE1 ruleset or authoritative
  deterministic analyzer exists. Real production findings, upgrade priorities,
  Manual Trade Recipes, production result binding, public authentication, and a
  public service remain unavailable. Fixture/fake success paths are not product
  availability claims.
- Public documentation: rewrote the English README and added a structurally
  matching professional Turkish README; added a changelog, improved security
  and contribution guidance, and added pull-request/issue templates. All public
  copy preserves the GGG non-affiliation notice and prohibited-integration
  boundaries.
- Hosting decision: [ADR 0014](adr/0014-laravel-cloud-staging.md) selects Laravel
  Cloud Starter for the first locked-down pre-alpha staging environment, using
  Frankfurt where available, a generated Cloud domain, and Serverless
  PostgreSQL. Valkey and queue/worker resources are demand-driven; Redis/Horizon
  remains the local/self-hosted path.
- Cloud limitation: the current encrypted queued-artifact handoff uses local
  storage, which is not durable across Laravel Cloud compute. Cloud imports stay
  disabled until reviewed private object storage exists. The dependency-free
  `/up` endpoint is the initial health probe; `/ready` currently also expects a
  Redis-compatible service.
- Cost posture: initial Cloud staging targets USD 20 per month with an absolute
  USD 25 operator ceiling. These are budgets rather than price guarantees, and
  current official Laravel Cloud pricing must be checked before resources are
  created.
- Validation evidence: Composer metadata/audit, Pint, PHPStan, 555 PHPUnit tests
  with 7,809 assertions, repository guardrails, architecture/parser/Policy Gate
  suites, 31 fast and 35 extended evals, clean npm install/audit, ESLint, Vue
  TypeScript, 15 Vitest tests, 7 Playwright tests, production asset build,
  Laravel config/route/view caches, YAML parsing, and local-link/documentation
  validation across 68 Markdown files passed. Native Windows plain
  `composer install` cannot satisfy Horizon's `ext-pcntl`; the documented
  Windows install with the exact `pcntl`/`posix` ignores passed. Docker and a
  local PostgreSQL server were unavailable, so the real PostgreSQL migration
  cycle remains encoded in CI and must pass on the final commit before staging.

## Change discipline

2026-08-20: implemented the PoE1 character catalog, seven-step analysis wizard,
Fortify authentication, member ownership, enum RBAC, mandatory admin 2FA,
append-only admin audit, member/admin Inertia surfaces and PostgreSQL-compatible
membership migrations. Wiki metadata was verified by HTTP but runtime and
migrations remain network-free. The final local verification passed 626 of 628
PHPUnit tests (the two disposable-PostgreSQL integration tests were skipped),
16 Vitest tests, Composer validation/audit, Pint, PHPStan, npm clean install and
audit, ESLint, Vue TypeScript, production build, 72-file documentation validation,
route inspection, and `git diff --check`. Port 5432 was closed and no local
PostgreSQL, Docker, Podman, `psql`, or `pg_isready` command was available, so a
real PostgreSQL fresh/rollback/reapply cycle remains a required pre-deployment
gate; SQLite success is not PostgreSQL evidence.

2026-08-20: added the policy-gated external-source boundary, poe.ninja economy
candidate implementation, immutable price evidence contracts, source-sync
migrations, disabled Wiki/GGG adapter skeletons, and PostgreSQL self-reference
migration-order test correction. Real PostgreSQL verification remains pending
because this workstation has no disposable PostgreSQL service or container
runtime; PHP 8.4 was available through Laravel Herd.

Each prompt ends by updating this file with status, decisions, commands, evidence, and unresolved risks. A prompt blocked by policy or provenance stays blocked; implementation convenience is not a reason to advance it.

2026-08-21: extended the existing Policy Gate, immutable snapshot and ruleset
lifecycle with one executable Source Registry projection, bounded normalized
staging, import reports, content-addressed replay, approval and policy-gated
staging rollback. GGG PoE1 passive-tree and conditional poe.ninja economy data
now stage before snapshot/read-model publication. Fixed disabled adapters for
documented GGG APIs, Wiki Cargo, PoE2 datasets, Atlas and RePoE have explicit
reasons and no HTTP client. The admin system registry is read-only except for a
super-admin-only, 2FA/recent-password/rate-limited/audited queue request using a
fixed source code. No Trade endpoint, scraper, arbitrary URL or credential path
was added. See ADR 0020 and the current validation report for actual gate
results; SQLite is not PostgreSQL proof.

Validation for this source-governance change: `composer validate --strict`,
`composer audit`, Pint, PHPStan, repository guardrails, documentation checks,
`git diff --check`, `npm run lint`, `npm run typecheck`, `npm run test` (21/21),
`npm run build`, and the focused source/import/policy suite (131 tests, 129
passed in the combined run with the two pre-existing opt-in PostgreSQL tests
skipped). The full backend suite then passed 817/819 tests with 10,732
assertions. After the final mock poe.ninja staging coverage, the complete suite
passed 818/820 tests with 10,745 assertions; the two PostgreSQL tests remain skipped because no local service is
listening on `127.0.0.1:5432`. A real PostgreSQL fresh/rollback/reapply run was
attempted and failed closed with connection refused; SQLite is not reported as
PostgreSQL evidence. No real external HTTP request was made; the poe.ninja
sync path is covered with `Http::fake`.

2026-08-20: extended the governed ruleset lifecycle with edition-scoped
canonical game-data contracts and persistence. `GameVersion`, `GameRuleset`,
historical/active ruleset repositories, compatibility statuses, and eleven
canonical entity types are framework-independent. PostgreSQL composite foreign
keys bind every canonical row to a same-edition immutable ruleset and source
snapshot; activation requires an approved import, approved provenance, and
compatible status. Fixture, legacy, invalid, unsupported, and unavailable
datasets fail closed. The approved GGG PoE1 passive-tree importer now supplies
only evidenced classes, Ascendancies, passive nodes, and keystones. No PoE2,
skill, item, modifier, or content-goal facts were invented. Admin catalog
inspection exposes checksums, import failures, activation, compatibility,
provenance, and entity counts without raw payloads or an edit surface. See ADR
0019. Composer validation/audit, Pint, PHPStan, 756 of 758 PHP tests with 9,571
assertions (two opt-in PostgreSQL tests skipped), architecture tests, guardrails,
the fast eval suite, npm clean install/audit/lint/typecheck, 21 Vitest tests, 8
Playwright tests, Vite build, docs validation, route listing, and diff checks
passed. A disposable SQLite fresh/rollback/reapply cycle passed. No local
PostgreSQL service, client, or container runtime was available and port 5432
was closed, so the real PostgreSQL fresh/rollback/reapply gate remains pending;
SQLite is not claimed as PostgreSQL proof.

2026-08-20: production-bound the versioned PoE1 deterministic finding engine.
It consumes only normalized PoB facts and the exact locally activated immutable
GGG passive-tree snapshot; both ruleset and snapshot checksums are reverified.
The initial rule codes cover missing character identity fields, core armour slot
completeness, PoB-reported elemental resistance versus its reported maximum,
negative chaos resistance, invalid mana reservation, disabled gems, explicitly
identified main-skill link count, item-slot conflicts, and unknown passive node
IDs. No Life/ES/DPS or build-archetype defensive thresholds were introduced.
Finding persistence, stable replay, missing-data omission, PlayerStat aliases,
raw-input log exclusion, and reanalysis added/resolved/unchanged diffs have
dedicated tests. Recommendation ranking and production Trade-recipe vocabulary
remain separate future gates.

2026-08-20: implemented the default-off operator importer for GGG's official
PoE1 `grindinggear/skilltree-export` root `data.json`. Upstream `master` was
inspected rather than assumed and pinned to commit
`8bd138b32ea2631455cac5935bfab089f826094f` (`3.29.1`), raw SHA-256
`7e9f755e33152129ebf36c2ebdad639c527e4ad70d274b1fefb860f30ca01122`.
The exact real file normalized 7 classes and 3,390 nodes into 1,575,398 bytes
with canonical snapshot SHA-256
`83abe75e2ce26b30005537452ad72079361cc7c56be1d0b9dfa632bcd08265e7`.
The command supports bounded absolute-file and exact commit-pinned raw-URL
input, dry-run, immutable snapshot import, quarantine without raw retention,
idempotent replay, immutable ruleset publication, and atomic activation. It
does not run in web requests or download image assets.
Final validation passed Composer metadata/audit, Pint, PHPStan, repository
guardrails, 681 of 683 ordinary PHP tests with 8,693 assertions (the two
opt-in PostgreSQL tests skipped in that ordinary SQLite run), clean npm install
and audit, ESLint, Vue TypeScript, 21 Vitest tests, the Vite production build,
76-file documentation validation, route inspection, and diff whitespace
checks. A disposable local PostgreSQL 18.4 cluster separately passed all 15
selected migration/lifecycle/import tests with 142 assertions, including
`migrate:fresh`, rollback, reapply, foreign-key type inspection, immutable
triggers, and atomic activation. The cluster and downloaded upstream files
were then removed.

2026-08-20: added the governed source and ruleset lifecycle described by ADR
0017. The existing policy registry and external sync-run table now back
content-addressed normalized snapshots, duplicate-checksum replay, revision
conflict quarantine, immutable published rulesets, exact source links, atomic
activation pointers, and append-only activation history. Canonical user, GGG
PoE1 skill/Atlas, Wiki, poe.ninja and prohibited RePoE records are seeded.
Wiki, poe.ninja and OpenAI explanations have independent default-off governance
switches. At this dated lifecycle milestone the production analyzer remained
unavailable; ADR 0018 later superseded that binding state. SQLite lifecycle and
policy tests pass. A disposable PostgreSQL 18.4 cluster on localhost validated
fresh migration, exact lifecycle foreign-key types, immutable triggers,
rollback/reapply, duplicate checksum replay, conflict quarantine, denied source
activation, and atomic ruleset activation: PostgreSQL migration tests passed
3/3 with 47 assertions and lifecycle tests passed 6/6 with 40 assertions (87
combined). The
temporary cluster was stopped and deleted after validation.
2026-08-20: expanded the character catalog and intake wizard to edition-scoped
PoE1 and PoE2 support. PoE2 baseline 0.5 records twelve classes (eight available,
four planned), twenty-two regular Ascendancies, and Witch/Lich-only alternate
Abyssal Lich. Planned classes and cross-game payloads fail backend validation;
ruleset-backed PoE2 findings remain approval-gated.
Local verification passed 641 of 643 PHP tests (the two opt-in disposable
PostgreSQL tests were skipped), 17 frontend tests, Composer validation/audit,
Pint, PHPStan, npm clean install/audit/lint/typecheck/test/build, route listing,
documentation validation, and diff whitespace checks. Source documentation
URLs returned HTTP 200. No PostgreSQL client, container runtime, or service was
available and port 5432 refused connections, so real PostgreSQL fresh/rollback/
reapply remains an explicit deployment gate; SQLite is not claimed as proof.

2026-08-20: implemented the Lootwright ARPG presentation system with semantic
OKLCH tokens, locally bundled Newsreader, DM Sans, and JetBrains Mono fonts,
two-pixel geometry, the 32px workbench grid, explicit rarity and unknown states,
and reusable evidence, item, affix, statistic, finding, upgrade, recipe, and
scope components. Landing, analysis overview, upgrades, and Manual Trade recipe
surfaces now use the system; `/style-guide` is a fixture-only component gallery.
No GGG assets, external requests, live listing claims, or fabricated prices were
introduced. Composer metadata/audit, Pint, PHPStan, 641 of 643 PHP tests with
8,305 assertions (two opt-in PostgreSQL tests skipped), clean npm install/audit,
Prettier, ESLint, Vue TypeScript, 21 Vitest tests, the production build,
Playwright behavior and responsive visual tests, 72-file documentation
validation, route inspection, and diff whitespace checks passed. A disposable
PostgreSQL service remains unavailable locally; SQLite is not claimed as
PostgreSQL evidence.

2026-08-21: added the framework-independent deterministic analysis contract.
`AnalysisEngine`, `AnalysisResult`, `AnalysisContext`, `AnalysisRule`,
`RuleRegistry`, and `RecommendationCandidate` are immutable, edition-scoped
domain types. PoE1 exposes a versioned rule registry over the existing reviewed
rules; the PoE2 registry and engine fail closed until an approved PoE2 ruleset
and data sources are available. Findings carry stable IDs, ruleset/edition
identity, evidence, provenance, unsupported-data disclosure, dependencies, and
an explanation trace in the canonical result projection. Golden, determinism,
cross-edition, unsupported-data, registry, and bounded benchmark tests were
added. Existing persistence JSON remains compatible with the prior finding
projection. PostgreSQL availability remains an external deployment gate.

2026-08-21: added the deterministic upgrade graph stage. Findings are mapped
by the PoE1 candidate factory to immutable upgrade nodes with prerequisites,
conflicts, dependent slots, affected findings, expected effects, stable score,
budget uncertainty, and explicit market-data requirements. Typed budget and
hard/soft user constraints preserve items, the main skill, and passive-tree
choices; violating candidates remain visible as impossible rather than being
silently applied. Unknown prices never become numeric claims. Topological
ordering rejects cycles, conflicts are deterministic, and PoE2 planning has no
fallback to PoE1. Added tests cover Mageblood/skill preservation, verified and
unknown budgets, conflicts, cycles, cross-slot dependencies, and edition
isolation.

2026-08-21: added the deterministic manual Trade Recipe Engine. Structured
upgrade requirements now pass through an edition-scoped vocabulary, the exact
canonical modifier registry, and a compatible approved ruleset before becoming
immutable broad/strict human-readable filters. Unknown mappings remain
`unsupported_filters`; positive vocabulary conflicts fail closed; dependent
slots are disclosed without asserting unproven stat loss. The engine emits no
Trade request payload, search URL, listing, price, seller action, or POESESSID.
PoE1 vocabulary is an explicit adapter; PoE2 remains a disabled fail-closed
contract. The recipe card copies only the selected manual text and shows
unsupported filters. New engine and UI tests pass; production actionable
recipes still require approved canonical modifier and Trade vocabulary imports.
Validation passed 923 of 925 PHP tests with 12,921 assertions (the two
environment-gated PostgreSQL tests skipped), PHPStan, Pint, repository
guardrails, clean npm install/audit, ESLint, Vue typecheck, 22 Vitest tests, the
production build, 86-file documentation validation, and diff whitespace
checks. This feature adds no migration and performs no network request.

2026-08-21: completed the optional AI runtime boundary around the existing
provider-neutral gateway and OpenAI Responses adapter. Narrow intent and
explanation ports, exact-edition explanation validation, independent global and
task switches, transactional hard-capped quota overrides, a persistent
single-probe circuit breaker, aggregate admin usage/cost projections, and
super-admin-only audited controls now fail closed around the existing Policy
Gate and outbound allowlist. Provider prompts and raw responses are not stored;
AI-off retains the manual intent, deterministic analysis, upgrade graph, Manual
Trade recipe, and local explanation path. Normal tests use fake transports and
do not make live provider calls. Final local validation passed Composer
metadata/audit, Pint, PHPStan, repository guardrails, 941 of 943 PHP tests with
13,105 assertions (the two disposable-PostgreSQL tests were skipped), npm clean
install/audit, ESLint, Vue typecheck, 22 Vitest tests, the production build,
87-file documentation validation, route inspection, and diff whitespace checks.
An isolated SQLite fresh/rollback/reapply cycle passed; no PostgreSQL service,
client, or container runtime was available and port 5432 was closed, so SQLite
is not claimed as PostgreSQL evidence. See ADR 0024 for the authority boundary.
