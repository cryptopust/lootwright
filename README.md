# Lootwright

Lootwright is a Laravel 13 application for evidence-backed Path of Exile build
analysis. A player submits build text or a PoB share payload, describes goals,
and receives deterministic findings, prioritized upgrade candidates, and a
human-readable manual Trade-filter recipe.

> This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

## What Lootwright is

Lootwright is a deterministic, edition-scoped analysis service. Findings use a
normalized build, immutable ruleset, and explicit evidence. Unknown facts remain
uncertain; AI is never game-mechanics authority.

## PoE1 support

PoE1 has bounded PoB1 parsing, edition checks, encrypted workflow persistence,
ruleset resolution, and a narrow production engine. Coverage is limited to the
rules and canonical data in the active approved ruleset and fails closed when
that ruleset is unavailable.

## PoE2 support

PoE2 has independent PoB2, canonical-data, ruleset, analysis, upgrade, and Trade
namespaces. It never falls back to PoE1 identifiers or formulas. Public/runtime
availability is separately gate-controlled and remains unavailable until its
activation and acceptance evidence are approved.

## Build import and deterministic analysis

Supported intake is explicitly submitted PoB XML/share-code content and bounded
item text where the selected adapter supports it. User URLs are never fetched.
Malformed encoding, XXE, oversized payloads, decompression bombs, excessive
depth/counts, and cross-edition data are rejected.

The workflow is parse → normalize → exact ruleset resolve → findings → upgrade
planner → manual Trade recipe → optional explanation. Results include hashes,
provenance, uncertainty, and stable decision traces.

## Trade and market intelligence

Lootwright does not automate the Trade site, issue undocumented API requests, or
purchase items. It emits descriptive manual filters. Approved market observations
are contextual, timestamped, cached with expiry, and never deterministic truth;
the poe.ninja adapter is disabled by default.

## AI assistant

OpenAI is optional and disabled by default. If explicitly enabled and allowed,
it may classify intent or explain existing findings in a closed schema. It cannot
invent items, modifiers, prices, links, rules, recommendations, or another
user's data. Provider failure falls back to deterministic behavior.

## Feature matrix

| Capability | Status | Evidence |
| --- | --- | --- |
| Laravel app, accounts, ownership, RBAC, 2FA, audit log | AVAILABLE | Feature-tested modules and routes |
| Safe PoB1 import and normalization | AVAILABLE | Bounded parser and security tests |
| PoE1 deterministic analysis | BETA | Production binding with narrow approved-rule coverage |
| PoE2 adapter and deterministic engine | BETA | Independent adapter/ruleset code; activation gate remains separate |
| Upgrade planner and manual Trade recipes | BETA | Deterministic planner and vocabulary validation |
| Market observations | EXPERIMENTAL | Policy-gated poe.ninja adapter, disabled by default |
| AI intent/explanations | EXPERIMENTAL | Strict gateway, budgets, fallback; disabled by default |
| PoE Wiki ingestion, remote build retrieval, automated Trade | PLANNED | No enabled production implementation |

AVAILABLE is implemented and tested; BETA is usable with scope limits;
EXPERIMENTAL is optional or release-gated; PLANNED is not shipped.

## Data sources and architecture

External sources pass through the deny-by-default Policy and Provenance Gate.
Rulesets/canonical data are immutable, checksum-bound, and edition-scoped. The
framework-independent engine is under `src/`; Laravel orchestration and
infrastructure are under `app/`; Inertia/Vue renders typed results only.

Laravel Cloud is the production platform. PostgreSQL is the system of record;
managed cache, queues, scheduler, and durable private object storage are enabled
only when required and reviewed. Scale-to-zero is preferred where safe. `/up` is
public liveness and `/ready` is protected diagnostics.

## Privacy, security, and current limitations

Hostile-input, XML, SSRF, XSS, CSRF, IDOR, SQL injection, queue replay, dataset
poisoning, and privilege-boundary defenses are tested. Raw inputs and AI prompts
are minimized, encrypted where retained, redacted from logs, and deletable.

Limitations: narrow mechanic coverage; external providers disabled by default;
separately gated PoE2 activation; no automated Trade actions; and live Cloud or
provider evidence must come from staging acceptance. See [live acceptance](docs/release/live-acceptance.md)
and [MVP readiness](docs/release/mvp-readiness.md).

## Local setup

Requirements: PHP 8.4, Composer 2, Node.js 24/npm 11, and PostgreSQL (or the
documented SQLite test profile).

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm ci
npm run build
composer run dev
```

Cloud deployment follows `docs/deployment/laravel-cloud.md`; never run
destructive commands against production.

## Testing

Run the repository Composer/npm quality gates, audits, build, and documentation
validator. Production acceptance additionally requires a dedicated environment
and `php artisan acceptance:gate`; fixture runtime is rejected.

## Contributing

Read [AGENTS.md](AGENTS.md), [CONTRIBUTING.md](CONTRIBUTING.md), and the relevant
architecture/policy documents. Preserve edition isolation, provenance,
determinism, privacy, and security; add tests and docs for capability changes.
