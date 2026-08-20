# Data Provenance Architecture

Provenance is executable authorization and result evidence, not a free-text
attribution field. Every external or user-supplied fact must retain a source
identity distinct from the game edition and ruleset that consumes it.

## Provenance layers

Lootwright keeps four layers separate:

1. **Source definition**: stable source code, source type, access mode,
   governance status, default state, and MVP scope.
2. **Source version and permission evidence**: exact source version, evidence
   URL, retrieval/effective dates, permission status, attribution, review
   summary, and policy version.
3. **Immutable source snapshot**: edition, URL, upstream revision, retrieval
   time, normalized and upstream SHA-256 checksums, content type, license
   identifier, schema version, status, and normalized payload.
4. **Ruleset and analysis reference**: a published ruleset links exact source
   snapshots; an analysis records ruleset/source identity, checksum, Policy
   Gate decision, and input evidence.

User-submitted PoB/item text, official GGG exports, PoE Wiki facts, and
poe.ninja market observations are never merged into an unattributed canonical
blob. Market evidence can provide visible context but cannot become game-rule
authority.

## Governed lifecycle

```text
registered source/version
    -> exact Policy Gate decision
    -> bounded operator import
    -> schema validation
       -> invalid: rejected snapshot + quarantine record
       -> valid: immutable normalized snapshot
    -> reviewed published ruleset links exact snapshot IDs
    -> atomic activation for edition + patch + league + parser
    -> exact local resolution during analysis
```

The database makes snapshots, published rulesets, source links, and activation
history append-only on PostgreSQL. Re-importing the same source/edition/checksum
is idempotent. Reusing an upstream revision with different content enters
quarantine. Corrections create a new snapshot and ruleset; published payloads
are not edited.

## Minimum imported-data identity

Every imported data set must be able to answer:

- Which stable source code supplied it?
- Which `GameEdition` does it belong to?
- Which game patch/version is represented, when known?
- What exact URL and upstream commit/revision were reviewed?
- When was it retrieved or imported?
- What are the upstream and normalized SHA-256 checksums?
- Which importer/parser and schema versions produced it?
- What content type and license/permission identifier apply?
- Is the record valid, rejected, quarantined, superseded, or active?
- Which permission evidence and Policy Gate decision authorized the operation?

Missing or contradictory answers fail closed. A feature flag cannot convert
`deny` or `require_review` into `allow`.

## Runtime boundary

User-facing requests read local, validated, edition-matched snapshots only.
They never synchronize PoE Wiki, poe.ninja, GGG exports, GitHub content, or any
other third party. Approved remote imports are separate operator commands or
scheduled synchronization processes with exact host/path/method allowlists,
public-address verification, redirects disabled, size/time bounds, identifiable
User-Agent configuration, locks, and the Policy Gate checked before HTTP.

The current official passive-tree importer accepts only a local file or the
configured exact commit-pinned `grindinggear/skilltree-export` raw URL. The
poe.ninja economy synchronizer is default-off and limited to reviewed PoE1
economy endpoints/categories. PoE Wiki Cargo is a disabled skeleton pending
licensing, redistribution, underlying-rights, attribution, and funding review.

## Analysis evidence propagation

A deterministic finding contains its stable rule code, exact ruleset identity,
input evidence keys, explanation trace, source provenance, and confidence. A
recommendation carries its edition and links findings/trace back to the same
analysis and ruleset. The portable export preserves hash-verified products and
provenance without including raw PoB text or lifecycle timestamps.

Optional AI receives only the minimum structured deterministic products. Its
schema can return explanation text for the exact supplied finding and
recommendation codes. It cannot submit canonical facts, new codes, prices,
source URLs, Trade identifiers, rules, or calculations. Invalid output is
discarded in favor of a deterministic template.

## Retention and privacy

Raw user imports are hostile private input, not governed public data sets.
Queued handoff is encrypted and short-lived; deletion/pruning removes the raw
object. Long-lived normalized products are owner-scoped, encrypted, and hashed.
Logs, policy audits, admin audit records, AI telemetry, fixtures, and exceptions
must not contain raw PoB, item text, cookies, secrets, complete prompts, or
provider response bodies.

See the [source register](../compliance/source-register.md), [source strategy
ADR](../adr/0017-game-data-source-strategy.md), and [external-source
architecture](external-data-sources.md).

