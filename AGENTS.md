# Lootwright Engineering Constitution

This file is binding for every contributor and coding agent. Product and policy decisions live in `docs/`; if code and documentation disagree, stop, update the governing decision first, and make the code conform.

## Product identity

- Brand: Lootwright.
- Primary domain: `lootwright.org`.
- Product: AI-assisted Path of Exile build analysis and item-search planning.
- Delivery order: Path of Exile 1 MVP, then a separate Path of Exile 2 adapter.
- Accepted user inputs: natural-language goals and a PoB/PoB2 share code or pasted item text deliberately submitted by that user.
- Outputs: deterministic findings, prioritized upgrade recommendations, and a human-readable manual Trade-filter recipe.
- Required visible notice: "This product isn't affiliated with or endorsed by Grinding Gear Games in any way."
- Lootwright is not a bot, overlay, executable client tool, browser extension, price-check overlay, live market indexer, scraper, browser automation tool, or trading automation system.

## Non-negotiable architecture

- Use a Laravel 13 modular monolith: one repository, one application deployment,
  one PostgreSQL database, and Laravel cache/queue abstractions. Local Docker or
  self-hosted deployments may use Redis and Horizon; Laravel Cloud may use Valkey
  and Cloud queue/worker facilities when an implemented feature requires them.
  Do not provision unused cache or queue resources, and do not make Horizon
  mandatory on Laravel Cloud.
- The web layer is Inertia 3, Vue 3 Composition API, TypeScript, Tailwind CSS, and shadcn-vue.
- Keep the deterministic domain and analysis engine in framework-independent PHP under `src/`. It must not import Laravel, Illuminate, Eloquent, HTTP, queue, cache, filesystem, or AI SDK types.
- Keep Laravel delivery and infrastructure code under `app/`. Controllers and jobs orchestrate application use cases; they do not contain game rules.
- Put PoE1 and PoE2 behind shared ports in separate namespaces and directories. A domain object must always carry a game identity. Never branch casually on game inside shared formulas or reuse one game's canonical identifiers in the other.
- Keep rulesets immutable after publication and identify each by game, patch, league when relevant, source version, parser version, and SHA-256 checksum.
- Route every external source, provider, endpoint, import, and outbound capability through the deny-by-default Policy and Provenance Gate.
- AI is optional. It may extract user intent into a closed schema or explain already-produced deterministic results. It may not create or alter canonical stat IDs, item IDs, rules, formulas, prices, Trade IDs, or links.
- The full analysis and manual recipe workflow must remain useful with every AI provider disabled.
- Do not add microservices, Kafka, Kubernetes, event sourcing, distributed workflows, or infrastructure not justified by measured MVP needs.

See [module map](docs/architecture/module-map.md), [data flow](docs/architecture/data-flow.md), and [game boundary](docs/architecture/poe1-poe2-boundary.md).

## Module boundaries

- `src/Domain/Shared`: value objects, evidence, provenance references, game identity, and shared ports only.
- `src/Domain/Analysis`: deterministic calculations and findings.
- `src/Domain/Recommendations`: deterministic ranking and rationale.
- `src/Domain/TradePlanning`: abstract, manual filter recipes; no Trade API identifiers or generated Trade links.
- `src/GameAdapters/PoE1` and `src/GameAdapters/PoE2`: parsers, rule interpretation, and game-specific mappings. PoE2 remains inactive until phase two.
- `src/Application`: provider-neutral use cases and ports. It may depend on domain packages; the domain may not depend on it.
- `app/Modules`: Laravel-facing identity, persistence, HTTP, queues, ruleset catalog, policy/provenance, and AI infrastructure.
- `resources/js`: Vue/Inertia presentation. It never performs authoritative calculations.

Modules exchange typed commands, queries, DTOs, and ports. They do not read another module's tables, Eloquent models, container bindings, or internal classes directly. Cross-module database constraints require an architecture decision record (ADR).

## Determinism and evidence

- Given the same normalized input, ruleset identity, parser version, and configuration, the engine must produce byte-stable canonical results.
- Use explicit decimal/rounding policies; never rely on locale, floating-point display defaults, wall-clock time, random values, or network state in calculations.
- Every finding and recommendation must reference input evidence and the exact ruleset identity used.
- Preserve raw user input only as long as necessary and separately from normalized facts. Never silently reinterpret a PoE1 payload as PoE2 or vice versa.
- Unknown, ambiguous, unsupported, or unproven facts produce typed uncertainty or a refusal, never a fabricated fallback.

## GGG, platform, and data policy

- Follow [GGG integration policy](docs/compliance/ggg-integration-policy.md) and [source register](docs/compliance/source-register.md).
- Only use resources documented by Grinding Gear Games (GGG), or third-party sources whose permission and provenance have been verified and recorded.
- Never call, probe, reproduce, or reverse-engineer undocumented GGG endpoints, including `/api/trade/search`, `/api/trade/fetch`, and `/api/trade/data/*`.
- Never scrape the official site, forums, Trade pages, or third-party build sites. A future approved API client must use only its documented host, path, method, scopes, rate-limit headers, and identifiable user agent.
- Never collect or request `POESESSID`, Path of Exile passwords, browser cookies, session credentials, or equivalent secrets.
- Never inspect the game process, memory, files, screen, clipboard, network traffic, or logs.
- Never automate keyboard or mouse input, gameplay, chat, whispers, invites, purchases, party actions, or Trade-site interaction.
- Never build a browser extension or executable overlay.
- Do not copy or redistribute GGG logos, artwork, music, item art, flavour text, or other protected assets. Do not imply GGG approval.
- Use Path of Exile names only as reasonably necessary to truthfully describe compatibility.
- Unknown permission or commercial-use status means disabled. Approval requires an updated source-register record and, when material, an ADR.
- Donations and all monetization remain disabled until documented policy/legal review approves them. Funding must never change functionality, quota, accuracy, adapters, priority, or access.

## Security and privacy

- Apply the [threat model](docs/security/threat-model.md) during design and review.
- Treat share codes, item text, natural-language goals, ruleset files, AI output, and all imported text as hostile input.
- Bound encoded and decoded sizes, decompression ratios, recursion, item counts, processing time, and queue retries. Disable XML external entities and network access during parsing.
- Do not fetch URLs found in user input. Prevent SSRF with an outbound allowlist enforced after DNS resolution and on redirects.
- Escape output by default. Sanitize any rendered Markdown or HTML. Do not use AI output as HTML.
- Never place secrets or personal data in source, logs, prompts, telemetry, exceptions, fixtures, screenshots, or analytics.
- Encrypt sensitive stored data, minimize retention, provide deletion, and redact logs. Do not retain raw imports by default once normalized unless a documented user-facing feature requires it.
- Require CSRF protection, secure cookies, authorization checks, rate limits, queue isolation, and idempotency for state-changing requests.
- Verify ruleset signatures/checksums and provenance before activation. A checksum mismatch fails closed.
- AI providers receive the minimum necessary redacted fields. Provider failure, schema mismatch, prompt injection, or policy denial falls back to deterministic behavior.

## Dependency policy

- Use Composer and npm only. Commit `composer.lock` and `package-lock.json`; CI uses `composer install` and `npm ci`.
- Target PHP 8.4 and Node.js 24 unless an ADR changes the baseline. Do not introduce pnpm, Yarn, Bun, or parallel lockfiles.
- Add a dependency only when the standard library or existing dependency cannot reasonably satisfy a concrete requirement.
- Pin direct dependency ranges deliberately, review transitive dependencies, licenses, maintenance activity, advisories, install scripts, and bundle/runtime cost.
- Prefer maintained packages with SPDX-declared licenses compatible with the project's MIT-licensed original code. Copyleft, source-available, non-commercial, custom, or unknown licenses require documented legal review before use.
- No remote runtime scripts, unpinned Git dependencies, abandoned packages, or packages that phone home without explicit policy approval.
- shadcn-vue components are vendored source: review generated diffs, accessibility, and upstream license notices like any other dependency.
- Dependabot/Renovate-style updates may propose changes but never bypass tests, policy review, or lockfile review.

## Git safety

- Inspect `git status --short --branch` before and after work. This workspace was not a Git repository when this constitution was created; do not assume history exists.
- Preserve user work and unrelated changes. Never delete, overwrite, reformat, stage, or commit files outside the requested scope.
- Never use `git reset --hard`, destructive checkout/restore, force-push, history rewriting, broad clean commands, or recursive deletion without explicit user authorization and verified targets.
- Do not commit secrets, generated credentials, `.env`, dependency directories, build output, dumps, imported user data, or publisher assets.
- Keep commits small and coherent. Use Conventional Commits, explain migrations and policy changes, and never claim tests ran when they did not.
- Architecture, source-policy, game-boundary, funding, or deterministic-behavior changes require corresponding documentation/ADR updates in the same change.

## Required quality gates

During the documentation-only phase, run:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/validate-docs.ps1
```

After Prompt 01 creates the Laravel application, the repository must expose and pass these exact commands before merge:

```powershell
composer validate --strict
composer audit
composer run format:check
composer run analyse
composer run test
npm ci
npm audit --audit-level=high
npm run lint
npm run typecheck
npm run test
npm run build
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/validate-docs.ps1
```

Required Composer scripts are `format:check`, `analyse`, and `test`; required npm scripts are `lint`, `typecheck`, `test`, and `build`. Prompt 01 must wire them to versioned tools. New deterministic rules require unit, fixture, property/boundary, checksum, and cross-game isolation tests. Integration changes require policy-gate denial tests before success-path tests.

## Definition of done

A change is done only when its tests and quality gates pass, deterministic behavior is evidenced, security and provenance impacts are recorded, PoE1/PoE2 isolation is preserved, user-visible copy includes required notices where relevant, docs and ADRs are current, and no disabled capability was enabled by assumption.
