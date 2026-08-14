# Data Flow

## Analysis path

```mermaid
sequenceDiagram
    actor User
    participant Web as Laravel / Inertia
    participant Gate as Policy + Provenance Gate
    participant Parser as PoE1 or PoE2 adapter
    participant Core as Deterministic core
    participant AI as Optional AI port

    User->>Web: goal + explicit share code/item text
    Web->>Gate: input type, game, capability, provenance
    Gate-->>Web: allow or typed denial
    Web->>Parser: bounded hostile input + parser version
    Parser-->>Web: normalized snapshot + diagnostics
    Web->>Core: snapshot + immutable ruleset identity
    Core-->>Web: evidence-backed findings
    Web->>Core: findings + user constraints
    Core-->>Web: ranked upgrades + manual recipe
    opt AI enabled and permitted
        Web->>AI: minimal redacted intent/results
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

### 7. Optional AI

- Intent extraction maps natural language into an allowlisted schema. Invalid or unsupported values are rejected or confirmed by the user.
- Explanation receives deterministic outputs, not authority to change them.
- Prompts exclude secrets, unnecessary raw imports, and personal data.
- Provider outage, denial, timeout, unsafe output, or schema failure preserves the deterministic result and uses template explanations.

### 8. Persistence and deletion

- PostgreSQL stores normalized snapshots, deterministic results, provenance references, and minimal audit metadata.
- Raw PoB input is never persisted. Authenticated, consented normalized import JSON is owner-scoped, idempotent, encrypted, defaults to 24-hour retention, is bounded by a 168-hour ceiling, and has capability-token deletion plus hourly expiry pruning.
- Redis contains disposable jobs, rate-limit counters, and cache entries, never the sole copy of a result.
- Logs contain opaque request IDs, not share codes, item text, credentials, or AI prompts.

## Failure behavior

All stages fail closed with typed outcomes: invalid input, unsupported parser, unsupported game/patch, provenance denied, ruleset unavailable, checksum mismatch, analysis limitation, or optional-provider unavailable. Retries are bounded and idempotent. A failure must never cause fallback to a different game, unverified source, undocumented endpoint, or AI-generated fact.
