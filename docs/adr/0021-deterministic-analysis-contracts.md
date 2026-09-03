# ADR 0021: Edition-scoped deterministic analysis contracts

Date: 2026-08-21

Status: accepted

## Decision

The deterministic domain exposes one shared orchestration contract but keeps
rule registries and mechanics in separate game adapters. `AnalysisEngine`
returns an immutable `AnalysisResult` containing only findings and
recommendation candidates backed by the exact `GameRuleset`. A finding carries
stable identity, edition, ruleset version, category, evidence, source
provenance, confidence, dependencies, unsupported-data disclosure, and its
explanation trace.

PoE1 delegates to the existing reviewed production rules through
`Poe1RuleRegistry`. PoE2 has a deliberate fail-closed adapter and empty rule
registry until an approved PoE2 dataset and ruleset are available. No PoE1
identifier, threshold, content goal, or formula is reused for PoE2.

The Laravel workflow continues to persist the established finding projection;
the richer `AnalysisResult` is included in the immutable output snapshot. This
keeps existing consumers compatible while making the canonical contract
available to future adapters and replay tooling.

## Consequences

Unknown and unsupported normalized properties are reported in typed result
metadata and suppress dependent rules. Missing Life, ES, DPS, armour, evasion,
and block evidence cannot create threshold findings. Same normalized input,
intent, ruleset, and engine version produce the same canonical JSON. AI remains
downstream and may only explain these existing deterministic products.
