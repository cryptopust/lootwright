# Contributing to Lootwright

Lootwright welcomes careful issue reports and design review. Code contributions
must respect the deterministic, provenance-first architecture and the project's
publisher-data boundaries.

Contributor licensing and trademark governance are still unresolved. External
patches may be discussed and reviewed, but maintainers must not merge the first
outside contribution until an approved inbound-contribution policy is published.

## Before you start

Read:

- `AGENTS.md` for binding engineering rules;
- `LICENSE-SCOPE.md` for publisher, third-party, and user-data boundaries;
- the relevant files under `docs/adr/`;
- `docs/compliance/source-register.md` before using any external fact or format;
- `docs/progress.md` for current pre-alpha status.

If code and a governing document disagree, update and review the decision first.

## Local setup

The required baseline is PHP 8.4, Composer 2, Node.js 24, npm 11, PostgreSQL,
and the PHP `dom`, `zlib`, and `pdo_pgsql` extensions. Docker Engine with Compose
v2 is the recommended Linux/WSL2 workflow.

```bash
cp .env.example .env
composer run setup:docker
composer run dev:docker
```

For a host installation with PostgreSQL and Redis already running:

```bash
cp .env.example .env
composer run setup
composer run dev
```

Native Windows cannot run Horizon because `pcntl` and `posix` are unavailable;
use WSL2/Docker or the repository's `setup:windows` and `dev:web` scripts.

## Required quality checks

Run the exact gate in `AGENTS.md` before requesting review:

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

Use the focused architecture, parser-security, Policy Gate, evaluation, and
browser scripts when your change touches those boundaries. Never weaken an
assertion merely to obtain a green run.

## Architecture rules

- Keep deterministic domain code under `src/` independent from Laravel,
  Eloquent, HTTP, queues, cache, storage, filesystem, clocks, and AI SDKs.
- Put Laravel delivery and infrastructure adapters under `app/`.
- Keep PoE1 and PoE2 adapters, identifiers, rulesets, caches, and fixtures
  isolated. No cross-edition fallback is allowed.
- AI may extract closed-schema intent or explain existing deterministic output;
  it cannot create facts, IDs, rules, filters, prices, sources, URLs, or
  recommendations.
- Every ruleset/source change needs exact version, checksum, parser/game scope,
  permission, provenance, and review evidence before activation.
- Do not add undocumented GGG endpoints, scraping, Trade automation, browser or
  game-client access, `POESESSID` handling, overlays, or gameplay automation.

## Fixtures and private data

Commit only tiny, original structural fixtures. Never submit real player builds,
private notes, prompts, credentials, cookies, protected publisher data, copied
game assets, logs, database dumps, or provider recordings. Authorized private
fixtures stay under ignored `evals/private/` storage and never update a public
golden baseline.

## Pull requests

Keep each pull request small and coherent. Explain:

- the concrete problem and chosen boundary;
- implementation and documentation changes;
- tests actually run and exact failures/not-run checks;
- migration, privacy, security, provenance, policy, and edition-isolation
  impact; and
- residual limitations or rollback needs.

Use Conventional Commits. Do not include generated dependencies, `.env`, build
output, credentials, imported user data, or publisher assets. Do not deploy or
change external resources as part of a code review.

This product isn't affiliated with or endorsed by Grinding Gear Games in any way.
