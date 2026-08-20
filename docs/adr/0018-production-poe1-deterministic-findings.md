# ADR 0018: Production PoE1 deterministic finding engine

Date: 2026-08-20

Status: accepted

## Context

The analysis workflow, PoB normalizer, immutable ruleset lifecycle, and official
GGG PoE1 passive-tree snapshots existed, but production still resolved
`UnavailableDeterministicAnalysisEngine`. Fixture findings were useful only for
evaluation and did not justify user-facing analysis claims.

## Decision

- Bind `DeterministicAnalysisEngine` to the PoE1 production adapter. It accepts
  only normalized `pob1` artifacts and resolves the exact game, patch, league,
  parser, ruleset version, and checksum before execution.
- Publish the deterministic manifest inside the immutable ruleset payload. An
  older tree-only ruleset remains immutable; re-importing the same governed
  snapshot publishes a distinct `-analysis.<engine-version>` ruleset version.
- Run pure rules in `src/GameAdapters/PoE1/Analysis`. Laravel infrastructure may
  load published local JSONB snapshots, but the pure engine has no Laravel,
  database, HTTP, filesystem, cache, queue, AI, clock, or random dependency.
- Verify the published ruleset checksum and linked
  `GGG-POE1-SKILLTREE-001` snapshot checksum again before each analysis. No
  request-time upstream access is permitted.
- Compare elemental resistance only with the matching maximum value reported by
  PoB. Do not assume a fixed cap. Negative chaos resistance is informational.
  Do not add Life, ES, DPS, armour, evasion, or block thresholds without a new
  reviewed ruleset decision and evidence.
- Treat helmet, body armour, gloves, and boots as the narrow first-party core
  slot completeness check. Off-hand and jewellery are not assumed mandatory.
- Apply the four-link review rule only when PoB explicitly identifies exactly
  one enabled main active gem. Ambiguous groups yield no link finding.
- Preserve unknowns. Conflicting PlayerStat aliases are omitted rather than
  selected. Missing stats do not become zero.
- Persist typed findings with stable rule code, exact ruleset, severity,
  category, observed/expected values, affected identifiers, evidence,
  provenance, and confidence. AI may explain these DTOs but cannot change them.
- Reanalysis compares canonical finding content by stable rule code and reports
  `added`, `resolved`, and `unchanged`; per-analysis UUIDs are not semantic
  finding content.

## Consequences

The first production vertical slice can persist real deterministic PoE1
findings when an operator has activated the exact governed snapshot. It still
does not claim production upgrade recommendations, exact item prices, or live
Trade results. Fixture analyzers remain confined to tests/evaluation and are not
container bindings.
