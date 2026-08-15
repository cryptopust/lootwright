# Deployment Security Checklist

Every production deployment must record the operator, UTC time, release SHA,
ruleset identities, environment, evidence links, and result for each item.

Packaging and rollout details are in the [deployment runbook](../operations/deployment.md),
[environment reference](../operations/environment-reference.md),
[backup/restore runbook](../operations/backup-restore.md), and
[release policy checklist](../operations/release-policy-checklist.md).

## Before deployment

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://lootwright.org`,
  trusted proxies/hosts are explicit, and HTTP redirects to HTTPS at the edge.
- [ ] `APP_KEY` comes from the secret manager, has never entered Git or CI logs,
  and rotation/re-encryption ownership is documented.
- [ ] Session cookies are Secure, HttpOnly, encrypted, SameSite=Lax, scoped to
  the narrow host/path, and the load balancer forwards the secure scheme.
- [ ] Public authentication remains absent or has the `authentication` limiter,
  session regeneration, logout invalidation, password confirmation, optional
  verification policy, CSRF, and horizontal/vertical authorization tests.
- [ ] `POLICY_ADMIN_ENABLED=false` unless a time-bounded maintenance window is
  approved. Any token is random, at least 32 characters, secret-managed, and
  rotated after the window.
- [ ] Imports, rulesets, links, AI, egress, and the global Policy Gate switch
  have an explicit expected state. Funding is false in code.
- [ ] `OPENAI_ENABLED=false` and `OUTBOUND_NETWORK_ENABLED=false` unless the
  exact provider policy, privacy notice, hard project spend cap, egress firewall,
  and smoke-test approval are current.

## Network and data stores

- [ ] PostgreSQL and Redis have no public listener or public security-group
  rule. Administrative access uses a private network and audited bastion.
- [ ] The Laravel runtime PostgreSQL role is LOGIN, NOSUPERUSER, NOCREATEDB,
  NOCREATEROLE, NOREPLICATION and has only required schema/table/sequence rights.
  A distinct migration role owns DDL and is unavailable to the web/worker
  process.
- [ ] PostgreSQL uses `sslmode=verify-full` with the expected CA and hostname.
  Statement/lock/idle transaction timeouts are set for the runtime role.
- [ ] Redis uses TLS, a dedicated ACL user, a random password, command/network
  restrictions, separate logical/prefixed queue/cache data, persistence as
  required, and a memory/eviction policy that cannot make Redis authoritative.
- [ ] Private artifact storage denies public ACLs, uses encryption, versioning
  only when compatible with deletion, lifecycle expiry at or below one hour,
  and a service identity limited to its prefix.

## Backup and restore

- [ ] PostgreSQL backups are encrypted with a separate key, access-audited,
  integrity checked daily, and retained no longer than 30 days unless approved.
- [ ] The latest quarterly restore test used an isolated no-egress network,
  restored schema/data, applied migrations, replayed deletion tombstones and
  retention pruning, checked counts and sampled hashes, and only then enabled
  read access. Evidence contains no decrypted user content.
- [ ] Recovery time and recovery point objectives are recorded and met. Redis
  loss is tested and does not lose authoritative results.

## Build and supply chain

- [ ] CI uses only `composer install` and `npm ci` with committed
  `composer.lock` and `package-lock.json`; no alternate lockfile exists.
- [ ] `composer validate --strict`, `composer audit`, Pint, PHPStan, PHPUnit,
  `npm audit --audit-level=high`, ESLint, Vue TypeScript, Vitest, Playwright,
  Vite build, and documentation validation pass on the release SHA.
- [ ] Dedicated architecture, parser-security, Policy Gate, fast-eval, repository
  guardrail, PostgreSQL migration, Compose-render, and non-root production-image
  checks pass. CI contains no registry push, artifact publication, or deploy step.
- [ ] Direct dependency/version/license/maintainer/install-script changes were
  reviewed. Production installs use Composer optimized autoload without dev
  packages and do not run unreviewed lifecycle scripts.
- [ ] Container images use approved pinned release references, run as a
  non-root user, have a read-only root filesystem where practical, no package
  manager/debug tools, and current OS/PHP/Node security patches.

## Runtime verification

- [ ] CSP contains no production `unsafe-inline` or `unsafe-eval`; HSTS,
  clickjacking, nosniff, referrer, permissions, opener, and resource headers are
  verified through the public TLS endpoint.
- [ ] Anonymous, account, cross-account, unverified-email, admin, export, and
  deletion probes return the expected 401/403/404/429 without redirect or data.
- [ ] Parser bomb, XXE, invalid encoding, deep XML, timeout, Policy Gate bypass,
  stale job, secret-redaction, and egress-denial tests pass.
- [ ] Horizon listens only to the expected isolated queues with documented
  memory/time/retry bounds. Unknown or old queued payloads are purged, not
  replayed.
- [ ] Scheduler, retention pruning, backup monitoring, dependency alerts,
  readiness, and incident alerts are healthy without logging secrets or raw
  input.

## Release decision

- [ ] Privacy jurisdiction, controller/security contact, age policy, backup
  provider terms, breach notification, and legal review blockers are resolved.
- [ ] The required non-affiliation notice is visible and no disabled GGG,
  Trade, automation, external connector, or funding capability was enabled by
  assumption.
- [ ] Rollback and all emergency switches were exercised in staging, and the
  on-call operator can follow the [incident response runbook](incident-response.md).
