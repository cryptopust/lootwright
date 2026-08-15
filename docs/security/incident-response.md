# Security Incident Response

Status: operational baseline. Named incident commander, security contact,
hosting provider contacts, and regulator timelines must be filled before public
deployment.

## Severity

- **SEV-1:** confirmed secret/database compromise, active cross-tenant access,
  destructive tampering, prohibited automation, or uncontrolled external calls.
- **SEV-2:** exploitable parser/authorization issue, material privacy exposure,
  ruleset integrity failure, or sustained queue/AI abuse without confirmed broad
  compromise.
- **SEV-3:** blocked attack, dependency advisory without demonstrated exploit,
  isolated availability issue, or control drift.

## First 15 minutes

1. Open a restricted incident record with UTC start time, reporter, affected
   environment, and an opaque correlation ID. Never paste raw PoB, prompts,
   session credentials, cookies, keys, or database rows into it.
2. Appoint an incident commander and one evidence recorder. Preserve relevant
   immutable infrastructure, access, Policy Gate, and aggregate queue logs.
3. Contain with the narrowest safe switch. If scope is uncertain, set
   `POLICY_GLOBAL_KILL_SWITCH=true`, `IMPORTS_ENABLED=false`,
   `RULESETS_ENABLED=false`, `OPENAI_ENABLED=false`,
   `OUTBOUND_NETWORK_ENABLED=false`, and `EXTERNAL_LINKS_ENABLED=false`.
4. Terminate Horizon workers after configuration propagation so no old process
   retains a capability. Do not retry or replay unknown queued payloads.
5. If credentials may be exposed, revoke first and rotate second: application
   keys require an explicit encrypted-data migration plan; OpenAI, database,
   Redis, readiness, policy-admin, mail, and deployment credentials can rotate
   independently.

## Investigation

- Identify the earliest known time, affected principals/records, source route,
  code and ruleset versions, policy decision, queue job ID, and external target.
- Compare immutable hashes and ruleset checksums. A mismatch is evidence of
  compromise, not a reason to recompute silently.
- Query by opaque owner/request hashes only. Access to decrypted user content
  requires incident-commander approval and must be recorded.
- For dependency incidents, preserve both lockfiles and the installed package
  inventory, then compare publisher, integrity, version, license, advisory, and
  install-script changes.
- For suspected SSRF, review guard denial outcomes, DNS answers, proxy/firewall
  logs, and redirect status. Do not reproduce the payload against a live private
  address.

## Eradication and recovery

1. Add a failing regression test that reproduces the boundary without real user
   data or live external calls.
2. Patch the smallest authoritative layer, run SAST, dependency audits, the full
   test/build gate, parser security tests, and policy denials.
3. Restore into an isolated network using the documented restore procedure.
   Apply deletion tombstones and retention pruning before allowing any worker or
   outbound connection.
4. Validate record counts, sampled hashes, migrations, least-privilege roles,
   queue emptiness, and emergency-switch state. Never restore Redis as the source
   of truth.
5. Re-enable one capability at a time. Egress and AI are last. Record the exact
   approver, time, version, and verification evidence.

## Notification and review

The incident commander obtains legal/privacy advice for user, provider, GGG, or
regulatory notification. Lootwright must not promise a deadline before the
deployment jurisdiction and controller contact are approved. User notices must
state known facts, affected data categories and period, containment, user
actions, and a contact. Do not speculate or expose another user.

Within five business days, publish an internal post-incident review covering
root cause, detection gap, blast radius, retained evidence, control changes,
tests, deletion/backup implications, and owners with deadlines. Policy,
source-register, threat-model, and ADR changes ship with the fix.
