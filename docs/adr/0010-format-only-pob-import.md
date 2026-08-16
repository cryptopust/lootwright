# ADR 0010: Format-only PoB Import

- Status: Accepted
- Date: 2026-08-14

## Context

Lootwright must accept build codes users deliberately paste or upload without
running Path of Building, fetching third-party URLs, or importing community game
datasets. PoB1 is the MVP. The maintained PoB2 repository uses the same outer
envelope but a distinct XML root and evolving fields.

## Decision

Approve only format interoperability at the commits and license hashes recorded
in the source register. Implement the envelope and XML reader independently in
framework-free PHP. PoE1 and PoE2 use separate parsers and normalizers behind a
shared intake port, with their concrete coordinator kept in the adapter layer
rather than provider-neutral `src/Application`. PoE2 is beta and cannot activate
PoE2 analysis, rulesets, or phase-two game data.

The parser never fetches a URL, executes Lua or embedded content, or invents a
game edition. A deliberately pasted canonical `https://pobb.in/{base64url-code}`
wrapper is accepted solely by extracting its one path segment locally; it makes
no request to pobb.in and is otherwise processed exactly as a pasted share code.
All other URLs remain rejected. `PathOfBuilding` is PoE1 evidence and
`PathOfBuilding2` is PoE2 evidence; conflicting or absent structural markers are
rejected. Unknown fields are reported explicitly. A parsed snapshot with an
unknown patch is not promoted to the existing analysis-grade `CanonicalBuild`;
exact ruleset binding remains a later fail-closed step.

## Consequences

- User-pasted and uploaded codes can be decoded locally with strict limits.
- No upstream source, dependency, dataset, formula, asset, or full build enters
  the repository.
- PoE2 compatibility is intentionally narrower and labelled beta.
- Parser-local identifiers are edition-prefixed, and PoE1-only choices are not
  consumed by the PoE2 adapter.
- Persistent results require a Policy Gate allow for an authenticated
  Lootwright owner, explicit consent, owner-scoped idempotency, encryption,
  bounded retention, and deletion; anonymous transient import remains allowed.
- Format or license drift immediately expires the allow record and requires new
  fixtures and review.
