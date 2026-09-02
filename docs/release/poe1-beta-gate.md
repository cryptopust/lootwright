# PoE1 public-beta gate

Validation date: 2026-09-02

Deployment `66501ba5b90e1778b9b9b1cd2d2d8e051c8c2558` is the verified
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

The following final gates remain open in this validation session: owner
password-confirmed deletion and deletion persistence, live controlled
low-resistance/attribute/CI/Resolute Technique differential submissions,
and a permanent live-suite expansion covering those cases. The healthy
baseline is already represented by the completed acceptance analysis. The
current fixture produced no actionable planner or recipe output; no market or
AI data was used. Reservation checks remain outside the release gate because
the acceptance fixture does not provide a supported reservation finding.

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

Decision: `NOT_READY` until canonical CI/RT node variants and owner-confirmed
delete are evidenced, with a permanent live-suite expansion.
