# Performance and Cloud Cost Budget

Status: measurement baseline, 2026-08-31. Targets are enforced only after
staging/Cloud telemetry confirms representative production traffic. Local SQLite
or fixture timings are not production evidence.

## Measurement contract

Enable `PERFORMANCE_TELEMETRY_ENABLED=true` only in a dedicated staging window
or sampled production window. Structured logs record route, status, latency,
response bytes, and hashed slow-query text; they never record SQL bindings or
user content. Capture queue, memory, AI token, and market-cache metrics from
Cloud/Laravel telemetry. Detect N+1 by comparing query count for one result
against a batch of results; no count increase proportional to child rows is
acceptable.

## Initial measurable targets

| Path / resource | Target (p95) | Gate |
| --- | ---: | --- |
| Build import + parse | ≤ 2,000 ms | FAIL if > 5,000 ms |
| Deterministic analysis | ≤ 3,000 ms | FAIL if > 8,000 ms |
| Analysis read/reload | ≤ 500 ms | FAIL if > 1,500 ms |
| Ruleset/canonical lookup | ≤ 100 ms | FAIL if > 300 ms |
| Manual Trade recipe generation | ≤ 500 ms | No automated Trade-site calls |
| Market lookup | ≤ 400 ms; cache hit ≥ 80% | DEGRADED on stale/no-price |
| AI explanation | ≤ 4,000 ms; bounded tokens | DEGRADED with deterministic fallback |
| API/Inertia response | ≤ 1 MB | FAIL if > 2 MB |
| Queue wait + execution | ≤ 60 s + job timeout | Alert on retries/failures |
| PHP memory | ≤ 256 MB/request | FAIL on sustained limit pressure |

## Safe optimizations

Immutable rulesets and canonical game entities are cacheable by edition,
ruleset identity, and checksum. Market observations are cached only by their
source/edition/league/category identity and expiry. User-private analyses,
prompts, and outputs are never shared across users. Inertia responses should
request expensive result sections lazily when the page supports partial reloads.

Cloud cost reviews must prefer scale-to-zero application compute and managed
PostgreSQL/cache/queue resources sized from observed utilization. Do not keep a
permanent worker, Redis instance, cron host, or object-storage tier running when
the measured workload does not require it. Durable object storage remains
required for retained raw artifacts; scratch filesystem storage is not durable.
