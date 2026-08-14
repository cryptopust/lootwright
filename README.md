# Lootwright

Lootwright is a Laravel 13 modular monolith for deterministic, evidence-backed
Path of Exile 1 build analysis and human-readable manual item-search planning.
The current implementation includes a bounded, local PoB1 importer and a
separate beta PoB2 format adapter. Game datasets, analysis formulas, AI
providers, market integrations, and donations are not implemented.

This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

## Runtime baseline

- PHP 8.4 with Composer 2
- Node.js 24 with npm 11
- PostgreSQL 18
- Redis 8 and Laravel Horizon
- Laravel 13, Inertia 3, Vue 3 Composition API, TypeScript, Tailwind CSS 4,
  shadcn-vue, and Vite 8

The versions in `composer.lock` and `package-lock.json` are authoritative. Docker
image tags intentionally track the supported PostgreSQL 18 and Redis 8 release
lines for local development.

## Recommended setup: Docker through Linux or WSL2

Install Docker Engine with Compose v2 on Linux, or Docker Desktop using its WSL2
backend on Windows. From a Linux/WSL2 shell in the repository root:

```bash
cp .env.example .env
composer run setup:docker
composer run dev:docker
```

Open <http://localhost:8000>. PostgreSQL and Redis are published only on the
loopback interface. Application dependencies, database data, and Redis data use
named volumes; the source tree remains bind-mounted for development.

To stop the development stack without deleting data:

```bash
docker compose down
```

## Host setup

Install PHP 8.4 with `dom`, `zlib`, `pdo_pgsql`, and `redis`, Node.js 24, npm 11,
PostgreSQL, and Redis. Copy `.env.example` to `.env`, change its local-only
example credentials if needed, then run:

```bash
composer run setup
composer run dev
```

Horizon requires the `pcntl` and `posix` extensions and therefore does not run
under native Windows PHP. On native Windows, use WSL2/Docker as recommended. For
web-only shell work with separately managed PostgreSQL and Redis, use:

```powershell
composer run setup:windows
composer run dev:web
```

The public liveness endpoint is `GET /up`; it only confirms that Laravel booted.
The deeper `GET /ready` endpoint checks PostgreSQL and Redis and is hidden unless
`READINESS_TOKEN` is configured and supplied in the
`X-Lootwright-Readiness-Token` header. Horizon is available at `/horizon` only in
the local environment and is denied elsewhere.

## Local PoB import

Run a small local fixture through the safe parser without database, Redis,
network, or OpenAI access:

```powershell
php artisan pob:import-fixture tests/Fixtures/Pob/poe1-minimal.xml
```

The web boundary accepts pasted raw share codes/XML or an uploaded `text/plain`
code at `POST /api/build-imports/pob`. URL fetching is intentionally absent. A
normalized result is transient unless `persist`, explicit `storage_consent`,
and an optional bounded `retention_hours` are supplied. Consented normalized
JSON is encrypted; its default retention is 24 hours and it can be deleted with
the one-time token returned by the endpoint. Run `php artisan
pob:prune-imports` to prune expired records; the scheduler runs it hourly.

Exact limits, supported fields, the pre-ruleset boundary, PoE2 beta status, and
privacy behavior are documented in [PoB import compatibility](docs/compatibility/pob-import.md).
The upstream notice is in [Path of Building format attribution](docs/compliance/path-of-building-attribution.md).

## Development commands

| Purpose | Command |
| --- | --- |
| Setup | `composer run setup` |
| Start web, Horizon, and Vite | `composer run dev` |
| Check PHP formatting | `composer run format:check` |
| Apply PHP formatting | `composer run format` |
| Static analysis | `composer run analyse` |
| Backend tests | `composer run backend-test` |
| Frontend lint | `npm run lint` |
| Frontend formatting check | `npm run format:check` |
| Frontend typecheck | `npm run typecheck` |
| Frontend component tests | `npm run test` |
| Production asset build | `npm run build` |
| Complete cross-platform gate | `composer run full-quality-gate` |

The merge gate also includes the exact commands listed in [AGENTS.md](AGENTS.md),
including the PowerShell documentation validator.

## Architecture boundary

Laravel delivery and infrastructure code belongs under `app/`. The future
framework-independent deterministic core belongs under `src/` and may not import
Laravel or infrastructure types. See the [module map](docs/architecture/module-map.md)
and [domain foundation](docs/architecture/domain-foundation.md), then check the
[capability matrix](docs/compliance/capability-matrix.md), [source
register](docs/compliance/source-register.md), and [delivery
progress](docs/progress.md) before adding application behavior.
