# Production Environment Reference

Status: safe-default reference for Laravel Cloud production. The canonical
copyable template is `deploy/env.production.example`; blank values are
intentional. Laravel Cloud injects managed resource connection values; do not
copy self-hosted Redis/Horizon assumptions over Cloud-injected settings. The
Docker/Compose package remains local/self-hosted compatibility tooling only.
Cloud production follows
[`docs/deployment/laravel-cloud.md`](../deployment/laravel-cloud.md) and must not
enable a capability until durable storage and managed queue evidence exists.

## External sources

`POE_NINJA_ENABLED=false` and `POENINJA_ECONOMY_ENABLED=false` are independent
source and governance switches; both must be true before the adapter can run.
If they are
true in production, `POE_NINJA_CONTACT` must be non-empty. The base URL must
remain `https://poe.ninja`; the refresh default is 1200 seconds and is clamped
to at least 300 seconds. `POE_WIKI_CARGO_ENABLED=false` and
`POEWIKI_IMPORT_ENABLED=false` must both be explicitly enabled after review;
`GGG_OAUTH_ENABLED=false` are disabled candidates. OAuth credentials remain
blank until an approved registration and least-privilege scope review exists.
`GGG_PASSIVE_TREE_IMPORT_ENABLED=false` is the independent operator-import
switch. URL mode additionally requires a real `GGG_PASSIVE_TREE_CONTACT` and
the temporary outbound switch; see the [import runbook](poe1-passive-tree-import.md).

## Application and HTTP boundary

| Variable | Required production value | Secret | Notes |
| --- | --- | --- | --- |
| `APP_ENV` | `production` | No | Other values fail production preflight. |
| `APP_KEY` | Secret-managed Laravel key | Yes | Encrypts retained content; rotation requires a re-encryption plan. |
| `APP_DEBUG` | `false` | No | Never enable for public requests. |
| `APP_URL` | Canonical `https://` origin | No | `https://lootwright.org` for the primary deployment. |
| `APP_RELEASE_SHA` | Lowercase 40/64-character digest | No | Must identify the reviewed source; image deployment also pins an OCI digest. |
| `LOOTWRIGHT_RUNTIME_MODE` | `PRODUCTION_CANONICAL` | No | Required by the live acceptance gate; local/testing harnesses use `TEST_FIXTURE` and cannot pass it. |
| `DEPLOYMENT_LOCKDOWN_MODE` | `true` initially | No | Enforces global and capability-off startup state. |
| `TRUSTED_HOSTS` | Comma-separated exact regexes | No | No wildcard; include only served hosts. |
| `TRUSTED_PROXIES` | Exact proxy IPs/CIDRs | No | Edge must strip spoofed forwarded headers. |
| `LOG_CHANNEL` | `stderr` | No | Redacted structured application logs only. |
| `PERFORMANCE_TELEMETRY_ENABLED` | `false` | No | Opt-in sampled profiling; enable only for a bounded Cloud measurement window. |
| `PERFORMANCE_SLOW_QUERY_MS` | `100` | No | Logs only hashed SQL text and duration above this threshold. |
| `RULESET_CACHE_SECONDS`, `CANONICAL_CACHE_SECONDS` | `3600` | No | Immutable, checksum-scoped cache TTLs; never contain user-private analysis. |

## PostgreSQL

| Variable | Baseline | Secret | Notes |
| --- | --- | --- | --- |
| `DB_CONNECTION` | `pgsql` | No | SQLite is tests/local only. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE` | Private exact endpoint | No | No public listener. |
| `DB_USERNAME`, `DB_PASSWORD` | Least-privilege runtime role | Password | DML only; no DDL ownership. |
| `DB_SSLMODE` | `verify-full` | No | Hostname and CA verification are mandatory. |
| `DB_SSLROOTCERT` | Mounted CA path | CA trust material | Must exist below the read-only secret mount. |
| `DB_SSLCERT`, `DB_SSLKEY` | Optional mTLS paths | Private key | Use only when the database requires client certificates. |
| `DB_APPLICATION_NAME` | Release/environment-specific name | No | Must not contain user or build identifiers. |
| `DB_MIGRATION_USERNAME`, `DB_MIGRATION_PASSWORD` | Separate DDL role | Password | Supplied only to the one-off `migrate` profile. |

## Redis, queue, cache, and session

| Variable | Baseline | Secret | Notes |
| --- | --- | --- | --- |
| `REDIS_HOST`, `REDIS_PORT` | Private exact endpoint | No | No public listener. |
| `REDIS_USERNAME`, `REDIS_PASSWORD` | Dedicated ACL identity | Password | Restrict command/key access to Lootwright prefixes. |
| `REDIS_SCHEME` | `tls` | No | Plain TCP is local/test only. |
| `REDIS_TLS_VERIFY_PEER`, `REDIS_TLS_VERIFY_PEER_NAME` | `true` | No | Both must remain enabled. |
| `REDIS_TLS_CA_FILE` | Mounted CA path | CA trust material | Exact deployment CA. |
| `REDIS_DB`, `REDIS_CACHE_DB`, `REDIS_QUEUE_DB`, `REDIS_HORIZON_DB` | `0`, `1`, `2`, `3` | No | Separation is defense-in-depth; ACL/prefix isolation is still required. |
| `REDIS_QUEUE_CONNECTION` | `queue` | No | Routes Laravel queues to the isolated Redis connection. |
| `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` | `redis` | No | Redis is disposable except for in-flight work/session availability. |
| `SESSION_ENCRYPT`, `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY` | `true` | No | SameSite remains `lax` for the current first-party UI. |
| `HORIZON_REDIS_CONNECTION` | `horizon-metadata` | No | Separate from queue payload keys. |
| `HORIZON_DASHBOARD_ENABLED` | `false` | No | Production UI is never exposed. |

## Health, policy, privacy, and retention

| Variable | Safe default | Secret | Notes |
| --- | --- | --- | --- |
| `READINESS_TOKEN` | Random 32+ characters | Yes | Protects `/ready`; liveness `/up` needs none. |
| `POLICY_GLOBAL_KILL_SWITCH` | `true` initially | No | Overrides all allows during lockdown/incident response. |
| `POLICY_ADMIN_ENABLED` | `false` | No | Maintenance-only boundary. |
| `POLICY_ADMIN_TOKEN` | Empty while disabled | Yes | Rotate after every approved window. |
| `IMPORTS_ENABLED`, `RULESETS_ENABLED`, `EXTERNAL_LINKS_ENABLED` | `false` initially | No | Enable individually only after release evidence and tests. |
| `AUTH_REQUIRE_VERIFIED_EMAIL` | `false` until accounts launch | No | Review account UX before enabling. |
| `INERTIA_SSR_ENABLED` | `false` | No | SSR is not a production dependency; enabling it requires a reviewed loopback renderer process. |
| `INERTIA_SSR_URL` | `http://127.0.0.1:13714` | No | Used only when SSR is explicitly enabled; never point it at a user-controlled or remote URL. |
| `ANALYSIS_RAW_ARTIFACT_TTL_MINUTES` | `60` | No | Maximum encrypted queue-handoff lifetime. |
| `POB_IMPORT_RETENTION_HOURS`, `POB_IMPORT_MAX_RETENTION_HOURS` | `24`, `168` | No | Consent and owner deletion still apply. |
| `ANALYSIS_RETENTION_DAYS`, `AI_AUDIT_RETENTION_DAYS` | `30`, `30` | No | Must not exceed approved schedule. |
| `DELETED_SESSION_TOMBSTONE_DAYS` | `7` | No | Unlinkable operational tombstones only. |

## AI, outbound access, and funding

| Variable | Required default | Secret | Notes |
| --- | --- | --- | --- |
| `OPENAI_ENABLED` | `false` | No | Deterministic workflow remains complete without AI. |
| `OPENAI_INTENT_ENABLED` | `false` | No | Independently denies provider intent extraction; the manual form remains available. |
| `OPENAI_EXPLANATIONS_ENABLED` | `false` | No | Independently denies provider explanations while preserving deterministic local fallback. |
| `POENINJA_ECONOMY_ENABLED` | `false` | No | Governance switch; cannot override Policy Gate or `POE_NINJA_ENABLED`. |
| `POEWIKI_IMPORT_ENABLED` | `false` | No | Governance switch; Wiki remains review-gated and disabled. |
| `GGG_PASSIVE_TREE_IMPORT_ENABLED` | `false` | No | Operator-only official export importer; enable only for a reviewed import window. |
| `GGG_PASSIVE_TREE_CONTACT` | Empty | Operator contact | Required for URL mode; never fabricate it. |
| `OUTBOUND_NETWORK_ENABLED` | `false` | No | Central egress guard denies before transport. |
| `OPENAI_API_KEY` | Empty | Yes | Not required for CI, health, deployment, or AI-off operation. |
| `OPENAI_LIVE_EVALS_ENABLED` | `false` | No | Live eval never runs in normal CI. |
| `OPENAI_CIRCUIT_FAILURE_THRESHOLD` | `3` | No | Consecutive provider failures before opening the persistent circuit. |
| `OPENAI_CIRCUIT_COOLDOWN_SECONDS` | `300` | No | Cooldown before one half-open probe is allowed. |
| Token/model/timeout/budget variables | Template values | No | Changing model/pricing requires official-document review. |
| `FUNDING_ENABLED` | `false` | No | Operator request only; code and Policy Gate still deny funding. |
| Funding decision/evidence/disclosure variables | Empty | No | Cannot be fabricated to enable funding. |

GGG OAuth credentials, `POESESSID`, payment-provider variables, live game datasets,
and Trade endpoint settings do not exist in the reference because Lootwright neither
needs nor accepts them.
