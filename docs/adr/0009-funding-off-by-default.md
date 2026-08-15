# ADR 0009: Funding Off by Default

- Status: Accepted
- Date: 2026-08-14

## Context

GGG's current policy language raises commercial-use questions, and payment systems add legal, privacy, security, tax, and governance obligations. Funding can also undermine equal access or appear to sell game-related advantage.

## Decision

Disable donations and all monetization by default. Do not implement payment providers, donation UI, billing data, sponsor tracking, or entitlements until a documented policy/legal review approves a precise model.

`FUNDING_ENABLED` is an operator request rather than an authorization. Runtime
status also requires canonical dated decision/evidence identifiers, explicit
operator acknowledgement, a versioned visible disclosure, and an executable
Policy Gate `allow`. The current rule and evidence explicitly deny activation.
No payment adapter exists, so `accepting_funds` remains false.

Funding may never unlock or influence functionality, quota, accuracy, adapters, priority, access, freshness, or support. Open-source licensing does not prove that monetization of GGG-related functionality or data is permitted.

## Consequences

- The MVP has no payment or entitlement attack surface.
- Hosting needs an unfunded or separately approved operating plan.
- Aggregate operating-cost projections are configuration-only and isolated from
  player, build, account, and future supporter data.
- Any future donation proposal requires a superseding ADR and updates to compliance, privacy, threat, tax/entity, and governance documents.
- If permission remains unclear, funding remains off indefinitely.
