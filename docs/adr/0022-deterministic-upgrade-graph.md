# ADR 0022: Deterministic upgrade graph and budget constraints

Date: 2026-08-21

Status: accepted

## Decision

Upgrade planning consumes only an `AnalysisResult`, edition-scoped
`BuildIntent`, a typed budget constraint, and explicit user constraints. The
planner emits an immutable graph rather than a flat or random item list.
Nodes identify prerequisites, conflicts, dependent slots, affected findings,
expected deterministic effects, score, budget uncertainty, and market-data
requirements. A topological dependency resolver orders nodes; circular graphs
fail closed.

Currency is a constraint, not a source of price facts. Without fresh,
policy-approved, timestamped `MarketPriceEvidence`, a candidate is visibly
classified as `requires-market-check` and may not claim a currency amount.

Hard constraints such as preserving Mageblood, preserving the main skill, or
avoiding a full passive-tree rebuild exclude violating candidates into the
explicit impossible list. Preferences can adjust score but cannot reorder
semantic priority outside deterministic scoring.

PoE1 and PoE2 candidate factories are independent. The initial production
binding registers only the PoE1 factory; no PoE2 mechanics or item assumptions
are borrowed while its ruleset remains unavailable.
