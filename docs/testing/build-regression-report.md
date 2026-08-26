# Build regression laboratory report

Status date: 2026-08-25

This report measures the committed project-created, real-shaped build corpus;
it does not use player accounts, share-code retrieval, live Trade data, or
production game-data tables. The XML documents follow the PoB1/PoB2 envelope
and contain realistic class, ascendancy, skill, support, item, resistance,
resource, and defence-summary shapes. They are deliberately labelled as
project-created fixtures and remain test-only.

Run the laboratory with:

```powershell
php artisan test tests/Feature/BuildRegressionLaboratoryTest.php
```

## Corpus inventory

| Edition | Cases | Archetypes represented | Parser result | Analysis result |
| --- | ---: | --- | --- | --- |
| PoE1 | 8 | melee, ranged attack, spell, minion, totem, mine, trap, DoT, crit, non-crit, armour, evasion, energy shield, hybrid, aura-heavy, mapping, bossing, Delve, Sanctum | 8/8 accepted | 8/8 complete |
| PoE2 | 3 | melee, ranged attack, spell (only categories represented by the project-created PoB2 shape) | 3/3 accepted | 3/3 unavailable, fail-closed |

PoE2 does not inherit PoE1 archetypes or findings. Its golden snapshot requires
`poe2_ruleset_unavailable` and `poe2_rules_not_approved`, and explicitly
requires an empty finding set.

## Golden regression policy

`tests/Fixtures/Builds/Poe1/golden.json` and
`tests/Fixtures/Builds/Poe2/golden.json` are reviewed golden projections of
the deterministic `AnalysisResult`: edition, engine/ruleset version, status,
finding-code order, and unsupported-data codes. Tests compare exact arrays and
fail on missing findings, unexpected findings, priority/order changes, edition
leakage, or ruleset-version changes. Golden files are never rewritten by a
test. A legitimate ruleset change requires a deliberate fixture and golden
diff in code review.

## Mutation and healthy-build results

The laboratory mutates a healthy PoE1 build in memory and verifies supported
defects:

| Mutation | Expected deterministic finding | Result |
| --- | --- | --- |
| Fire resistance 75 -> 60 | `defence.fire_resistance.below_reported_max` | Detected |
| Reserved mana above total | `resources.mana.reservation_invalid` | Detected |
| Unknown passive node | `passive_tree.node.unknown` | Detected |
| Support compatibility corruption | No guessed `skills.support_incompatible` rule | Safely unsupported |
| Unreserved mana below zero | `resources.mana.unreserved_negative` | Detected |
| Main gem disabled | `skills.gem.disabled` | Detected |
| Cold resistance below maximum | `defence.cold_resistance.below_reported_max` | Detected |
| Required unique removed | No guessed `equipment.required_unique.missing` rule | Safely unsupported |

All eight healthy PoE1 cases produce no findings. This is a precision proxy,
not a claim that every game mechanic is modelled.

## Measured quality metrics

| Metric | Calculation | Observed |
| --- | --- | ---: |
| Finding precision proxy | healthy cases with zero unexpected findings / healthy cases | 8/8 = **100%** |
| Regression stability | PoE1 replay byte comparisons that match / comparisons | 8/8 = **100%** |
| Mutation detection proxy | supported defect mutations detected / supported mutations | 6/6 = **100%** |
| Unsupported-data rate | PoE2 analyses returning unavailable unsupported codes / PoE2 cases | 3/3 = **100%** (intentional fail-closed state) |
| PoE1 ruleset coverage | registered PoE1 rule codes exercised by this lab / registered codes | 6/14 = **42.9%** |
| PoE1 parser corpus coverage | accepted PoE1 corpus cases / PoE1 corpus cases | 8/8 = **100%** for this corpus shape |
| PoE2 parser corpus coverage | accepted PoE2 corpus cases / PoE2 corpus cases | 3/3 = **100%** for this beta shape |
| Recommendation coverage | corpus cases producing recommendations / cases | 0/8; planner evidence requires an approved production ruleset and candidate vocabulary |

The ruleset and parser percentages are corpus/registry measurements, not game
coverage percentages. The 42.9% rule figure reflects only the rules exercised
by explicit mutations; it is not a claim that 42.9% of Path of Exile mechanics
are implemented. Recommendation coverage is zero because this laboratory is
the analysis-engine gate and the approved production Trade vocabulary is not
yet available.

## Limitations and next gate

- PoE1 fixtures are project-created, not downloaded player builds. They prove
  parser and deterministic boundary behavior without redistributing user data.
- PoE2 has no approved canonical ruleset, so unavailable output is the only
  valid golden result and no PoE1 rule may appear.
- Current deterministic PoE1 rules cover data quality, equipment slots,
  resistances, mana, skill links/disabled gems, and passive-node membership.
  Damage, recovery, ailment, reservation semantics beyond reported mana,
  unique-item requirements, and support compatibility remain explicit gaps.
- A staging run with consented real player exports is still required before a
  release claim can be made. This report must not be interpreted as live-player
  analysis evidence.

Required notice: This product isn't affiliated with or endorsed by Grinding
Gear Games in any way.
