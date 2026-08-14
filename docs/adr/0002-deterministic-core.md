# ADR 0002: Framework-independent Deterministic Core

- Status: Accepted
- Date: 2026-08-14

## Context

Build findings and upgrade recommendations must be reproducible, testable, explainable, and useful without AI or external systems. Laravel, storage, clocks, providers, and network state would make authoritative calculations harder to audit.

## Decision

Implement parsing contracts, normalized domain facts, analysis, ranking, and manual recipe construction as pure PHP packages with no Laravel or infrastructure dependency. For identical canonical input, configuration, parser version, and ruleset identity, canonical output must be byte-stable.

Calculations use explicit units, precision, rounding, ordering, uncertainty, evidence references, and tie-breaks. The core receives time or configuration only as explicit values. AI cannot write canonical facts or alter results.

## Consequences

- Unit, property, fixture, mutation, and snapshot tests can exercise the engine without the framework.
- Results can be cached and compared by content identity.
- Domain DTOs and ports require more deliberate design than Eloquent-first code.
- Presentation explanations must reflect deterministic rationale instead of introducing new claims.
- Any intentional algorithm change requires versioned expectations and migration notes.

