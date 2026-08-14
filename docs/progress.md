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

- Status: Pending.
- Scope: implement source/capability records, exact-operation matching, checksums, expiry, approval audit, emergency disablement, and deny-first tests.
- Gate: every external adapter is unreachable without an active exact allow record; undocumented GGG endpoints are structurally absent.

## Prompt 04 — Versioned ruleset catalog

- Status: Pending.
- Scope: implement immutable ruleset identities, import staging, checksum verification, review/activation, supersession, and exact game/patch/league resolution without adding unapproved game data.
- Gate: tampering, ambiguous version, game mismatch, expired provenance, and parser incompatibility fail closed.

## Prompt 05 — PoE1 PoB import

- Status: Pending and blocked on `POB1-FORMAT-001` approval in the [source register](compliance/source-register.md).
- Scope: implement bounded PoE1 share-code decoding/parsing from a pinned, licensed, provenanced format; preserve diagnostics and input digest.
- Gate: decompression bombs, XXE/DTD/network access, malformed structures, unsupported versions, and PoE2 payloads are rejected safely.

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

- Status: Pending.
- Scope: transform recommendations into descriptive item categories, ranges, priorities, alternatives, and relaxation order for manual entry.
- Gate: output has no Trade API IDs, query payloads, links, prices, listings, browser behavior, or undocumented endpoints.

## Prompt 11 — Optional provider-neutral AI

- Status: Pending and blocked on an approved provider record.
- Scope: add opt-in constrained intent extraction and deterministic-result explanation through the neutral AI port, plus template fallback.
- Gate: redaction, schema validation, prompt-injection, timeout, cost/rate controls, provider-off behavior, and canonical-write prohibitions are tested.

## Prompt 12 — Persistence, privacy, and workspace lifecycle

- Status: Pending.
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
- Scope: approve PoB2/ruleset sources, create an isolated PoE2 adapter, add PoE2 UI/persistence support, and run shared-port plus cross-game negative tests.
- Gate: no PoE1 identifiers/rules leak into PoE2, no speculative mappings ship, and a new ADR explicitly activates PoE2.

## Change discipline

Each prompt ends by updating this file with status, decisions, commands, evidence, and unresolved risks. A prompt blocked by policy or provenance stays blocked; implementation convenience is not a reason to advance it.
