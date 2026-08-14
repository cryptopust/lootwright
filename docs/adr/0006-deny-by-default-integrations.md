# ADR 0006: Deny-by-default Integrations

- Status: Accepted
- Date: 2026-08-14

## Context

GGG permits only documented resources under changing policies, third-party data may have unclear provenance or commercial rights, and provider configuration can accidentally broaden access. A conventional feature flag does not prove permission.

## Decision

All external capabilities pass a central Policy and Provenance Gate. An operation is allowed only when an active record exactly matches its source, operation, game, version, permission, purpose, data handling, authentication, rate limits, checksum, review, and expiry.

Missing, ambiguous, expired, broadened, redirected, or checksum-mismatched records deny execution. Denial cannot be overridden by AI, user input, environment variables, administrators, or fallback behavior. Undocumented GGG endpoints and scraping have no approvable capability under this constitution.

## Consequences

- New integrations require visible evidence and negative tests before code paths activate.
- The system can revoke a capability quickly when policy changes.
- Some technically available data remains unavailable to the product.
- Maintainers carry an explicit source-review burden instead of implicit trust.

