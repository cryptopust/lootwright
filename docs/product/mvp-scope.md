# MVP Scope

Status: target scope, not current product availability. The repository is
pre-alpha; the approved ruleset and authoritative PoE1 analysis release gates
remain open.

## PoE1 MVP

The first releasable product supports Path of Exile 1 only.

### In scope

- A web-only Laravel application with Inertia/Vue user experience.
- Explicit user entry of a natural-language build goal.
- Explicit user submission of a PoE1 PoB share code and/or pasted PoE1 item text, after parser provenance is approved.
- Strict input limits, game selection, parser diagnostics, and unsupported-input errors.
- A normalized PoE1 build snapshot tied to parser and ruleset versions.
- Deterministic build findings supported by input evidence and rule references.
- Deterministic, prioritized upgrade recommendations with assumptions and trade-offs.
- Human-readable manual Trade-filter recipes using descriptive fields and ranges. The user reproduces the recipe in the official Trade UI.
- Optional provider-neutral AI intent extraction and explanations with deterministic fallback.
- PostgreSQL persistence plus Laravel cache/queue abstractions where asynchronous
  work is justified. Local/self-hosted environments may use Redis/Horizon;
  Laravel Cloud resources are provisioned only when an implemented feature
  needs them.
- Source provenance, ruleset checksums, audit records, deletion controls, and required GGG independence notice.

### MVP release gates

- No required network call to GGG, OpenAI, or a third-party data source.
- No undocumented endpoint, Trade API ID, generated Trade link, price, or market claim.
- Every result is reproducible from normalized input plus immutable ruleset identity.
- Policy/provenance denials are tested before any external capability can be enabled.
- Security limits cover encoded input, decompression, parsing, queues, rendering, and retention.

## Dual-game catalog and ruleset boundary

PoE1 and PoE2 catalog, intake, wizard selection, and persistence are active
under ADR 0016. PoE2 remains Early Access and ruleset-backed analysis is still
approval-gated; an unavailable ruleset fails closed.

Future ruleset work may add:

- a separately licensed and provenanced PoB2 parser;
- PoE2-specific normalized facts, rulesets, calculations, findings, recommendations, and manual recipes;
- UI selection and presentation that make game identity impossible to miss; and
- contract tests proving shared ports work without importing PoE1 identifiers or formulas.

Phase two does not reuse PoE1 rules by convenience. Shared concepts are promoted only when their semantics are proven equivalent. PoE2 data never backfills missing PoE1 data, and PoE1 fixtures never validate PoE2 behavior.

## Migration impact

Repository inspection on 2026-08-14 found an empty directory: no Git repository, application files, package manifests, or generic/Demo ARPG foundation existed. There is therefore no legacy migration or deletion in Prompt 00. If a generic foundation appears later, work must pause for a file-by-file migration plan that maps generic game concepts to shared ports, moves PoE1 rules into its adapter, quarantines unsupported demo data, and preserves user work and history.

## Deferred decisions

- Exact PoB/PoB2 parser sources and licensing.
- Which deterministic PoE1 analyses make the first vertical slice.
- Data-retention defaults and anonymous versus account-based workspaces.
- Any GGG API integration; current registration availability and policy make it unnecessary for MVP.
