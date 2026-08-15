# Data Flow

## Analysis path

```mermaid
sequenceDiagram
    actor User
    participant Web as Laravel / Inertia
    participant Gate as Policy + Provenance Gate
    participant Store as PostgreSQL + encrypted object storage
    participant Outbox as Transactional workflow outbox
    participant Queue as Redis / Horizon
    participant Parser as PoE1 or PoE2 adapter
    participant Core as Deterministic core
    participant AI as Optional AI port
    participant Egress as Exact outbound guard

    User->>Web: goal + explicit share code/item text
    Web->>Gate: input type, game, capability, provenance
    Gate-->>Web: allow or typed denial
    Web->>Store: owner/session-scoped metadata + encrypted raw handoff
    Web->>Outbox: parse intent in same PostgreSQL transaction
    Outbox->>Queue: idempotent parse job after commit
    Queue->>Parser: bounded hostile input + exact adapter
    Parser-->>Store: immutable normalized snapshot + hash
    Parser->>Outbox: analysis intent + edition/ruleset identity
    Outbox->>Queue: bounded deterministic-analysis job
    Queue->>Gate: exact ruleset source/version + requested actions
    Gate-->>Queue: allow or typed policy block
    Queue->>Core: snapshot + immutable ruleset identity
    Core-->>Store: hashed immutable findings/recommendations/recipe
    opt AI enabled and permitted
        Web->>Egress: exact operation + fixed HTTPS target + public DNS
        Egress->>AI: redirect-disabled minimal redacted intent/results
        AI-->>Web: schema-valid intent or explanation
    end
    Web-->>User: deterministic result; AI annotation optional
```

## Stage invariants

### 1. Intake

- PoB edition is proven from the exact validated XML root; conflicting or absent evidence is rejected rather than guessed.
- Accepted input is text only: a goal, a share code, and/or pasted item text.
- No clipboard read, file discovery, account lookup, browser state, or URL fetch occurs.
- The web boundary enforces encoded size, rate, content type, and authorization limits before queueing work.

### 2. Policy and provenance

- The requested capability and source must have an active allow record.
- Missing, expired, ambiguous, checksum-mismatched, commercially uncertain, or game-mismatched records deny execution.
- Denials are user-safe and auditable without logging sensitive raw input.

### 3. Parse and normalize

- The selected game adapter owns decoding and parsing.
- Decoding has hard limits on compressed size, expanded size, expansion ratio, nesting, node/item count, and execution time.
- XML parsing disables DTDs, external entities, XInclude, and network access.
- The output is a canonical normalized snapshot with game, parser version, warnings, unsupported fields, and input digest.
- Parser uncertainty remains explicit; it cannot be filled by AI.

### 4. Ruleset resolution

- Select by exact game and patch, plus league when relevant.
- Verify source record, parser compatibility, activation state, and SHA-256 checksum.
- Never silently select latest when the input patch is unknown. Ask the user or return a typed unsupported-version result.

### 5. Deterministic analysis

- Pure functions consume normalized facts and immutable rules.
- Calculations use documented rounding and ordering.
- Each finding cites input evidence, rule identifier, ruleset identity, severity, certainty, and machine-readable rationale.
- Network, database, clock, locale, random, and AI state are absent from calculation functions.

### 6. Recommendation and manual recipe

- Deterministic ranking uses declared objectives, constraints, benefits, costs-as-unknown where price data is absent, and tie-break rules.
- Recommendations never claim current prices or availability.
- Trade planning emits descriptive fields, ranges, alternatives, and human steps. It emits no Trade API IDs, query payloads, browser actions, or generated Trade links.
- Each slot recipe resolves labels and constraints only from the exact
  checksum-bound ruleset vocabulary, carries the source finding trace on every
  filter, and emits strict plus structurally relaxed broad variants.
- Unmapped modifiers and patch-inapplicable rarity, corruption, influence,
  fracture, or affix constraints become typed unresolved requirements with a
  clarification question; no adjacent label or edition fallback is allowed.
- A separate Policy Gate decision permits one generic query-free official
  Trade homepage link. The plain-text copy rendering contains no URL.

### 7. Optional AI

- A local deterministic intent parser runs first; simple closed-vocabulary requests do not consume provider quota.
- Intent extraction maps natural language into strict BuildIntent or Clarification schemas and resolves every term against the exact edition/patch vocabulary. Invalid or unsupported values are rejected or confirmed by the user.
- Explanation receives deterministic outputs, not authority to change them.
- Prompts exclude secrets, PoB input, unnecessary raw imports, complete private notes, and personal data. Provider requests are stateless, tool-free, token-bounded, opt-in, policy-gated, and budget-reserved.
- Provider outage, denial, timeout, unsafe output, or schema failure preserves the deterministic result and uses template explanations.
- All provider egress is disabled independently by default and requires an
  exact scheme/host/port/path match, public DNS answers, and no redirect.
- PostgreSQL stores opaque usage/cost/validation metadata, never raw prompts or provider responses by default. Privacy-permitted cache entries contain only validated structured output and have bounded TTL.

### 8. Persistence and deletion

- PostgreSQL stores encrypted normalized snapshots, relational deterministic
  product projections, provenance/policy references, and minimal audit metadata.
- The synchronous format-only endpoint never persists raw PoB input.
  Authenticated or expiring privacy-session analysis uses an encrypted private-object-storage handoff
  because request memory cannot cross a queue boundary. Its database row stores
  only the opaque key and minimum metadata; the object is deleted immediately
  after parse or terminal rejection and has an hourly-pruned one-hour ceiling.
- Normalized imports and analysis input/output snapshots are encrypted,
  owner-scoped, content-hashed, and immutable. Concurrent duplicate requests
  resolve through a unique owner-scoped idempotency hash.
- Findings, recommendations, and recipes use encrypted hash-verified payloads
  with only bounded codes/order/severity/priority in searchable columns.
- Parse and analysis outbox rows are committed with the state transition that
  requires them. Commit callbacks publish promptly; the minute scheduler
  recovers pending rows. Publisher and job retries are bounded independently.
- Redis contains disposable jobs, rate-limit counters, and cache entries, never the sole copy of a result.
- Logs contain opaque request IDs, not share codes, item text, credentials, or AI prompts.
- User deletion removes artifacts, analyses, and prior retained imports through
  typed module ports. Only unlinkable aggregate deletion counts remain for
  operational evidence.
- Portable analysis JSON is canonical and timestamp-free. It contains
  hash-verified deterministic input/output, products, ruleset/source references,
  and policy state; it contains no raw share code or lifecycle timestamps.

### 9. Informational funding status

- `FUNDING_ENABLED` records an operator request only. The funding application
  port also requires canonical dated decision/evidence metadata, explicit
  operator acknowledgement, a versioned disclosure, and an executable exact
  Policy Gate allow decision.
- The current funding rule/evidence denies activation. No payment provider,
  solicitation, supporter identity, badge, entitlement, or revenue link exists.
- Monthly low/base/high projections use configuration-only traffic, hosting,
  token, and official dated pricing assumptions. The calculation reads no
  player, build, account, analysis, or supporter data and writes to none of
  those stores.
- Product request schemas prohibit donor/sponsor state, so it cannot enter
  deterministic inputs, recommendations, queue priority, quotas, or output.

### 10. Reproducible release evaluation

- Versioned public structural cases exercise the real import boundary, AI schema
  enforcement through a fake provider, and Manual Trade generation without network
  access. Synthetic finding cases are labelled as harness-only until production
  rulesets and the deterministic engine are available.
- Fast CI and manually invoked extended suites apply critical zero-tolerance edition,
  trace, replay, unsupported-data, hallucinated-ID, and network gates plus bounded
  latency, memory, token, and estimated-cost ceilings.
- Stable baselines contain hashes and non-sensitive outcomes, not raw fixtures,
  prompts, notes, or user identifiers. Golden changes require a passing public run,
  a named reviewer, a specific reason, and semantic diff review.
- Live-provider evaluation is a distinct default-off command. It requires explicit
  operator confirmation, a hard cost cap, Policy Gate and budget approval, synthetic
  or authorized/redacted private input, and a non-CI process.

## Failure behavior

All stages fail closed with typed outcomes: invalid input, unsupported parser, unsupported game/patch, provenance denied, ruleset unavailable, checksum mismatch, analysis limitation, or optional-provider unavailable. Only typed transient failures are retried with bounded backoff; invalid input and policy denial are terminal. Atomic state claims and immutable writes make duplicate jobs safe. A failure must never cause fallback to a different game, unverified source, undocumented endpoint, or AI-generated fact.
