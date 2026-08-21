# ADR 0020: Staged external-source imports and fixed adapter registry

Date: 2026-08-21

Status: accepted

## Context

ADR 0017 established the source-definition, immutable snapshot and ruleset
lifecycle, but fetch adapters could still normalize directly into their local
read model and the admin system page did not expose one policy-aware registry.
Technical reachability cannot establish permission, and an environment switch
must never become source authority.

## Decision

- Extend `policy_data_sources`; do not add a second source-definition table.
- Add policy/commercial/cache/reference metadata and project it through the
  framework-neutral `SourceRegistry` port.
- Register a fixed `ExternalSourceAdapterCatalog`. Operational status requires
  configuration, current Policy Gate evidence and no kill switch. Disabled
  official-API, Wiki, PoE2, Atlas and RePoE adapters contain no HTTP client.
- Normalize remote data into bounded staging rows before snapshot approval.
  Store SHA-256 identities and reports, not raw response bodies. Use a
  content-derived unique import identity for concurrent idempotency.
- Approve staging only after a matching immutable, valid, same-edition source
  snapshot exists. Canonical game data or economy read models may be updated
  only after approval.
- Policy-gate staging rollback separately. It may clear unapproved staging
  payloads and append a rollback report, but never mutate a snapshot, published
  ruleset or canonical row. Published rollback is prior-version activation.
- Manual UI imports are queued and accept only a fixed source code plus audit
  reason. Require super-admin authorization, verified active account, 2FA,
  recent password confirmation, rate limits and an atomic source lock.

## Consequences

The GGG PoE1 passive-tree and conditional poe.ninja economy paths now share a
staging invariant without sharing source-specific schemas. No import occurs in
a user request. Database queue/cache drivers remain sufficient for the MVP.
Policy-pending contracts are visible to administrators with explicit reasons
but cannot make network requests. PostgreSQL remains the authoritative
migration target; SQLite tests are fast feedback only.
