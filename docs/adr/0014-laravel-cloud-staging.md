# ADR 0014: Laravel Cloud for Initial Staging

- Status: Accepted
- Date: 2026-08-16
- Supersedes: the mandatory Redis/Horizon deployment aspect of ADR 0001; the
  Laravel modular-monolith decision remains accepted

## Context

ADR 0001 selected PostgreSQL, Redis, and Horizon as one coherent deployment
shape for the modular monolith. That shape remains useful for local Docker and
self-hosted operation, but it should not force unused infrastructure into the
first pre-alpha staging environment.

The current repository has a tested application foundation and substantial
format, policy, persistence, AI-containment, and fixture UI infrastructure. It
does not have an approved production ruleset or deterministic game analyzer.
The first staging environment therefore exists to validate the application
shell, PostgreSQL migrations, health/security boundaries, and deployment
operations. It must not imply end-user MVP availability.

Laravel Cloud provides managed application compute, generated Cloud domains,
Serverless PostgreSQL, optional Valkey, queue/background facilities, task
scheduling, and object storage. Application compute and deploy-command
filesystems are ephemeral. The existing local encrypted artifact disk cannot be
treated as cross-deployment or cross-worker durable storage.

## Decision

- Use Laravel Cloud Starter as the first staging platform, with a pre-alpha
  environment in Frankfurt when the region is available to the account.
- Use the generated `*.laravel.cloud` environment domain. Do not configure a
  custom domain for the initial stage.
- Attach Laravel Cloud Serverless PostgreSQL as the system of record.
- Keep cache, session, and queue access behind Laravel abstractions. Use
  database-backed cache/session settings for the foundation stage when
  appropriate.
- Do not provision Valkey until an enabled feature requires shared low-latency
  cache, rate-limit, or session state. If provisioned, use the Cloud-injected
  connection settings and the existing Redis-compatible Laravel adapter.
- Do not provision a managed queue or worker merely because queue contracts
  exist. Before asynchronous analysis is enabled, configure a Cloud managed
  queue or reviewed Cloud worker/background process and verify retry, timeout,
  stale-job, and observability behavior.
- Horizon remains supported for local Docker and self-hosted environments. It
  is not required for Laravel Cloud, and its dashboard remains disabled outside
  local development.
- Enable the Laravel Cloud scheduler only when scheduled retention/outbox work
  is active. Laravel's scheduled commands remain idempotent and use
  `withoutOverlapping` where appropriate.
- Treat application and deploy-command filesystems as ephemeral. Keep queued
  artifact workflows disabled until a reviewed durable private object-storage
  disk is configured; never rely on container-local files across requests,
  workers, deployments, or scale-to-zero wakeups.
- Use Scale to Zero or hibernation where the selected resource supports it and
  wake behavior is acceptable. Managed queues are preferred when uninterrupted
  background processing becomes necessary.
- Set an initial monthly operating target of USD 20 and an absolute ceiling of
  USD 25. These are operator budgets, not price guarantees. Verify current
  Laravel Cloud pricing and configure a platform spending limit before creating
  resources.

## Consequences

- PostgreSQL remains mandatory for staging; Valkey, workers, and queue resources
  are demand-driven rather than architectural defaults.
- The deterministic domain and application ports remain independent of Laravel
  Cloud, Valkey, Horizon, and any concrete queue implementation.
- The first Cloud environment runs in policy lockdown and is suitable only for
  pre-alpha staging verification. Production analysis, imports requiring queued
  raw handoff, AI egress, external links, and funding remain disabled until
  their separate gates pass.
- The protected `/ready` endpoint must be configured consistently with attached
  dependencies. `/up` remains the dependency-free Cloud health probe.
- Dockerfiles and the self-hosted deployment package stay in the repository for
  local development, CI packaging, and future self-hosting; they are not the
  required Laravel Cloud runtime.
- A later ADR may add Cloud object storage, Valkey, managed queues, a custom
  domain, or larger compute only after measured need and budget review.
- The modular monolith can later move to another managed platform or the
  existing self-hosted image without changing the domain model or public
  application ports.

