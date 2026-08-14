# ADR 0006: Deny-by-default Integrations

- Status: Accepted
- Date: 2026-08-14

## Context

GGG permits only documented resources under changing policies, third-party data may have unclear provenance or commercial rights, and provider configuration can accidentally broaden access. A conventional feature flag does not prove permission.

## Decision

All external capabilities pass a central Policy and Provenance Gate. An operation is allowed only when an active record exactly matches its source, operation, game, version, permission, purpose, data handling, authentication, rate limits, checksum, review, and expiry.

Missing, ambiguous, expired, broadened, redirected, or checksum-mismatched records deny execution. Denial cannot be overridden by AI, user input, environment variables, administrators, or fallback behavior. Undocumented GGG endpoints and scraping have no approvable capability under this constitution.

The executable outcome is only `allow`; `require_review` is a non-executable
state. Emergency environment and persisted kill switches may narrow or revoke
access but can never create an allow. Decisions are persisted without raw user
input, secrets, prompts, or unnecessary personal data.

## Consequences

- New integrations require visible evidence and negative tests before code paths activate.
- The system can revoke a capability quickly when policy changes.
- Some technically available data remains unavailable to the product.
- Maintainers carry an explicit source-review burden instead of implicit trust.
