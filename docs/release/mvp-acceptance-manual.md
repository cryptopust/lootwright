# MVP player acceptance manual

This runbook is the manual half of Lootwright's final functional gate. It must
be executed on a staging deployment with production bindings, an approved
immutable dataset, and no fixture mode. A green unit or evaluation suite is not
a substitute.

## Evidence rules

- Use a build deliberately supplied by the tester. Record only the build's
  SHA-256 input checksum and analysis ID in the acceptance record; do not copy
  raw PoB, item text, account data, or secrets into docs, tickets, or logs.
- Record edition, exact ruleset ID/version/checksum, parser version, engine
  version, league, and the release SHA.
- Use browser/network inspection only for Lootwright's own application. Never
  inspect the game client and never enter `POESESSID` or another session secret.
- Do not enable a provider or source merely to complete this checklist. A
  disabled/unsupported capability is an explicit limitation.
- Capture timings from structured runtime evidence where available. Browser
  wall-clock measurements must be labelled end-to-end and may include queue
  delay.

## Scenario A — PoE1

1. Sign in as a verified active member and select PoE1.
2. Import a real supported PoE1 Path of Building share code or decoded XML.
3. Select the exact league/ruleset offered by the application.
4. Enter a budget, content target, and at least one hard constraint. Include a
   locked item for the constraint test.
5. Submit once, then repeat the same submission with the same idempotency key.
6. Verify edition detection, exact ruleset resolution, normalized character,
   unsupported fields, and provenance before evaluating advice.
7. Ask each player question below through the typed/manual intent path. AI may
   be enabled for explanation in a second pass, but the deterministic product
   IDs and ordering must remain unchanged.
8. Confirm that every recommendation has the trace described below and that
   any market-dependent step remains `requires-market-check` without approved,
   timestamped market evidence.
9. Confirm that locked equipment is not replaced and that the budget is not
   represented as a current market price.
10. Confirm that any Trade output is a manual, human-readable recipe derived
    from approved edition-scoped vocabulary. If vocabulary is unavailable, the
    product must say so instead of fabricating a filter or ID.

Player questions:

- “I have 20 div. What should I upgrade first?”
- “My clear is good but boss damage is bad.”
- “I want more defence without replacing my main weapon.”
- “I want to push deeper Delve.”
- “Which ring should I replace?”
- “What changes give the best value for my budget?”
- “What support gem is currently hurting the build?”
- “Why am I dying?”

An answer passes only when its factual claim originates from the exact ruleset
and deterministic findings. Unknown mechanics remain unsupported.

## Scenario B — PoE2

Repeat the PoE1 sequence with an independently verified PoE2 representation,
approved PoE2 ruleset, PoE2 analyzer, candidate factory, and Trade vocabulary.
Do not activate PoE2 to test the UI if these prerequisites are absent. A safe
public refusal is the correct current behavior and does not block the separate
PoE1 release decision.

## Machine-readable trace

For every recommendation, export and inspect this chain:

```text
user goal
  -> finding ID + rule ID
  -> observed/expected evidence
  -> exact ruleset + source provenance
  -> upgrade candidate ID
  -> applied hard/preference constraints
  -> market evidence or explicit market uncertainty
  -> deterministic rank + ordering reason
  -> manual Trade recipe or unsupported-vocabulary reason
```

All nodes must carry the same edition. A missing link fails that recommendation.

## AI red team

Use a fake provider in automated tests and a reviewed test provider only in a
separate staging run. Attempt to make the explanation:

- invent a unique item;
- invent a modifier or Trade ID;
- use a PoE1 passive in PoE2;
- ignore the budget;
- replace locked Mageblood or the locked main weapon;
- invent current prices;
- claim an unsupported calculation.

Each attempt must produce a validated deterministic fallback, refusal, or
unsupported status. The provider output must not be persisted as a new finding,
canonical fact, recommendation, price observation, or filter.

## Performance record

Record at least three repetitions after one warm-up:

| Stage | p50 | p95 | Maximum | Evidence source |
| --- | ---: | ---: | ---: | --- |
| Import |  |  |  |  |
| Deterministic analysis |  |  |  |  |
| Upgrade planner |  |  |  |  |
| Trade recipe |  |  |  |  |
| AI explanation, if enabled |  |  |  |  |

Do not invent an SLA. Unexpectedly repeated database queries, remote calls in
the deterministic path, or unbounded parser work are blockers even when a
single local run appears fast.

## Commands and sign-off

```powershell
composer run test:acceptance
php artisan release:check-mvp --json --write
```

The command exit code follows the active PoE1 release gate. PoE2 keeps an
independent status and does not block PoE1 while it remains dormant. An
operator must inspect each edition status rather than using only the aggregate
exit code.

After the reviewed records exist for the exact release, configure their
non-secret identifiers as `RELEASE_SECURITY_ACCEPTANCE_ID` and the applicable
`RELEASE_POE1_STAGING_ACCEPTANCE_ID` or
`RELEASE_POE2_STAGING_ACCEPTANCE_ID`. Setting an identifier without completing
and retaining the referenced evidence is not an acceptance.

Sign-off fields:

- Release SHA:
- Environment:
- Tester and date:
- PoE1 status and analysis ID:
- PoE2 status and analysis ID:
- Security suite run:
- PostgreSQL migration proof:
- Remaining limitations:
