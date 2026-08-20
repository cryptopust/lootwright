# Lootwright

[Türkçe](README.tr.md)

Lootwright is an open-source, pre-alpha foundation for traceable,
deterministic Path of Exile build analysis and human-readable manual item-search
planning. The project is a Laravel 13 modular monolith with an infrastructure-
independent PHP domain core and an Inertia/Vue interface.

> This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

Lootwright can use cached poe.ninja economy data for visible market context. It
cannot fetch live Trade listings; it generates manual official Trade filter
recipes instead. Prices are estimates with visible source and freshness.

The dual-game PoE1/PoE2 catalog and wizard, Fortify-backed membership, owner-scoped analysis workspace and
server-authorized member/admin panels are available. Create the first verified
super-admin with `php artisan lootwright:admin:promote user@example.com --force`.

Lootwright is not a public service or a completed end-user MVP. It does not yet
have an approved production game ruleset or an authoritative production
analysis engine, so it cannot currently provide real build findings, upgrade
recommendations, or production Manual Trade Recipes.

## What works today

- Laravel 13, Inertia 3, Vue 3, TypeScript, Tailwind CSS, and shadcn-vue
  application foundations.
- A framework-independent domain and application layer under `src/`, protected
  by automated dependency-boundary tests.
- Edition-scoped value objects, DTOs, ports, provenance records, workflow
  states, persistence mappings, deletion, and portable export contracts.
- Separate PoE1 and PoE2 namespaces with negative tests preventing identifier
  and ruleset crossover.
- Bounded, local, format-only PoB1 import and a separately labelled beta PoB2
  format reader. This is structural interoperability, not full upstream-format
  parity or production game analysis.
- A deny-by-default Policy and Provenance Gate, hardened parser boundaries,
  security headers, rate limits, redacted logging, and emergency switches.
- A provider-neutral optional-AI gateway and a default-off OpenAI Responses
  adapter. Production provider execution remains policy-blocked; normal tests
  use fakes and deterministic fallback.
- Deterministic recommendation and Manual Trade Recipe contracts exercised with
  original fixture vocabulary. These test harnesses are not production game
  advice and never query live listings or prices.
- Responsive Turkish/English fixture screens, health/readiness endpoints,
  reproducible evaluations, and CI/production-packaging foundations.

See [MVP readiness](docs/release/mvp-readiness.md) for the strict release verdict
and [delivery progress](docs/progress.md) for the historical implementation log.

## What is planned

- Approve and publish an immutable PoE1 ruleset with exact source permission,
  version, checksum, parser compatibility, and provenance.
- Implement and independently verify a narrow authoritative PoE1 deterministic
  analysis and upgrade-prioritization slice.
- Bind production analysis pages to owner-scoped application results instead of
  fixture data.
- Add durable object storage before enabling queued raw-artifact handoff, then
  complete staging backup/restore, privacy contacts, and public account UX.
- Approve PoE2 deterministic rulesets independently before enabling ruleset-backed
  findings; catalog and intake support do not authorize cross-game fallback.
- Publish only after security, policy, provenance, deletion, and operations
  blockers are resolved.

## Game scope

Path of Exile 1 and Path of Exile 2 can both be selected in the versioned
character catalog and analysis intake wizard. PoE2 Early Access planned classes
remain unselectable. Neither game currently has an approved production ruleset;
unsupported operations fail closed and cross-edition fallback is forbidden.

## Design principles

- Deterministic calculations before generative wording.
- Exact evidence and ruleset identity before confidence claims.
- AI is optional and cannot invent game facts, modifiers, filters, prices,
  sources, URLs, or recommendations.
- Unknown or unsupported facts produce typed uncertainty or refusal.
- No scraping, undocumented Trade endpoints, live market indexing, browser or
  game-client access, automation, overlays, or session-cookie collection.
- The core workflow must remain usable when every AI provider is disabled,
  unavailable, or out of budget.

## Architecture

- `src/Domain`: immutable, framework-independent domain contracts.
- `src/Application`: transport-neutral use cases, DTOs, and ports.
- `src/GameAdapters/PoE1` and `src/GameAdapters/PoE2`: isolated format and
  edition adapters.
- `app/Modules`: Laravel HTTP, PostgreSQL, queue, storage, policy, identity, and
  optional provider infrastructure.
- `resources/js`: Inertia/Vue presentation; never authoritative calculation.

PostgreSQL is the system of record. Laravel cache and queue abstractions isolate
the application from the runtime. Local Docker and self-hosted deployments may
use Redis and Horizon. The first staging target is Laravel Cloud Starter in
Frankfurt, using Serverless PostgreSQL and a generated `*.laravel.cloud` domain.
Valkey and Cloud queue resources are added only when an enabled feature needs
them; Horizon is not required on Laravel Cloud.

See the [module map](docs/architecture/module-map.md), [system context](docs/architecture/system-context.md), and [Laravel Cloud ADR](docs/adr/0014-laravel-cloud-staging.md).

## Local setup

Required baseline: PHP 8.4, Composer 2, Node.js 24, npm 11, PostgreSQL, and the
PHP `dom`, `zlib`, and `pdo_pgsql` extensions. The committed lockfiles are
authoritative.

### Docker on Linux or WSL2

Install Docker Engine with Compose v2, then run:

```bash
cp .env.example .env
composer run setup:docker
composer run dev:docker
```

Open <http://localhost:8000>. The local stack uses PostgreSQL, Redis, and
Horizon; its data services bind to loopback and use named Docker volumes.

### Host installation

With local PostgreSQL and Redis available:

```bash
cp .env.example .env
composer run setup
composer run dev
```

Horizon needs `pcntl` and `posix`; use WSL2/Docker on Windows, or the web-only workflow:

```powershell
composer run setup:windows
composer run dev:web
```

Run the original structural fixture without database, queue, network, or AI:

```powershell
php artisan pob:import-fixture tests/Fixtures/Pob/poe1-minimal.xml
```

## Quality gates

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

Additional gates include `composer run ci:guardrails`, `composer run test:architecture`,
`composer run test:parser-security`, `composer run test:policy-gate`,
`composer run eval:fast`, and `npm run test:e2e`.

## Deployment

The first deployment is a locked-down pre-alpha staging environment on Laravel
Cloud Starter. It uses Frankfurt where available, the generated Cloud hostname,
and Serverless PostgreSQL. The initial monthly target is USD 20, with an
absolute USD 25 ceiling; these are operator budgets, not billing guarantees.

Follow the [Laravel Cloud guide](docs/deployment/laravel-cloud.md). Docker and
Horizon packaging remains available for local or self-hosted use and is not a
Laravel Cloud requirement.

## Security, contribution, and license

Use [SECURITY.md](SECURITY.md) for private vulnerability reports; never publish
credentials, private builds, prompts, cookies, or exploit details. Read
[CONTRIBUTING.md](CONTRIBUTING.md) before proposing a change. Ruleset and source
changes require verified permission and provenance.

Lootwright-original code and documentation are MIT licensed. [LICENSE-SCOPE.md](LICENSE-SCOPE.md)
explains what the project license does not cover, including GGG material,
third-party data, and user submissions. See also [third-party notices](THIRD_PARTY_NOTICES.md).
