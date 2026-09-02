# PoE1 public-beta gate

Validation date: 2026-09-02

Deployment `1bf965335da3f5222c83d19bb721c3f6303776ca` is the verified
production revision. Laravel Cloud production has exactly one managed worker
for each queue, using the database connection and the bounded commands in
`docs/operations/queue-runtime.md`. Automatic parsing and analysis probes,
including an idle-period probe, passed without a manually run `queue:work`.

The authenticated QA User 1 analysis completed with PoE1 ruleset
`01a05dca-26e5-7329-8e99-3ed46be85e58`, version
`3.29.1-analysis.1.0.0.skilltree.8bd138b3`, checksum
`6d5b31892ee364afba6d73b964ecf3c402b74faff31c25ddbe227a2550d4829e`.
Import, queued processing, deterministic result, save/reload, and export
passed. A second synthetic account was registered and operator-verified;
view/API/export access to User 1's analysis returned 404 and cross-owner delete
returned 423, so no User 1 data was changed.

Final gates were exercised in the guarded live suite: owner-confirmed
deletion, deletion persistence, canonical CI/Resolute Technique variants,
low-resistance and attribute actionable planner/recipe variants, and
save/reload with cross-owner denial. The healthy baseline is allowed to have
empty planner/recipe output. No market or AI data was used. Reservation checks
remain outside the release gate because the acceptance fixture does not provide
a supported reservation finding.

Health classification is `EXPECTED_CLOUD_EDGE_READINESS_PROTECTION`: `/up`
returns 200, `/ready` 404, and `/status` 403 while route registration is
present and private readiness is not weakened. Failed jobs remained zero.

Live differential submissions on 2026-09-02 produced the following evidence:
healthy baseline had no findings; low resistance at FireResist=50/75 produced
`defence.fire_resistance.below_reported_max`, one recommendation, and one
manual POE1 recipe; attribute 20/100 produced
`attributes.requirement.missing`, one recommendation, and one recipe. These
two variants pass their semantic and actionable-recipe gates. The attempted CI
and Resolute Technique mutations used display names in the PoB `nodes` field;
the normalizer correctly treated those as non-canonical node identifiers, so CI
was not recognized and RT suppression cannot be claimed. The CI result exposed
the ordinary life finding, therefore it is not evidence for CI semantics.

Owner-confirmed deletion was not completed: the password-confirmation page
requires the authenticated confirmation form and no deletion request was
issued. The permanent live suite remains the original 3/3 guarded tests and
does not yet include ownership/delete/variant assertions.

The owner-confirmed delete flow was completed for disposable analysis
`01a0632e-5cdd-7189-b7cd-3749192e7237`: after Fortify confirmation, delete
returned the dashboard redirect and subsequent detail/API/export reads all
returned 404. Saved listings no longer contained the record. (The initial
confirmation request itself returned to `/dashboard`; no middleware was
bypassed.)

Canonical keystone IDs were resolved from the active production snapshot:
Chaos Inoculation `passive:11455`, Resolute Technique `passive:31961`.
After deployment `1bf9653`, live numeric-node probes confirmed normalized
keystones `Chaos Inoculation`/`passive:11455` and `Resolute Technique`/
`passive:31961`. CI emitted no generic life finding or life recommendation;
RT emitted no crit-dependent finding or recommendation.

The permanent live suite contains nine guarded tests. With all required tests
passing, the release decision is `LIVE_POE1_BETA_READY_WITH_LIMITATIONS`.
