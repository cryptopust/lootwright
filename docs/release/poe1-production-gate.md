# PoE1 production gate

## Repository gate

The gate passes when the canonical parser, ruleset loader, deterministic
analysis engine, planner, and manual recipe builder complete the bundled
acceptance build without a fixture binding. Golden and mutation tests cover
healthy, low-resistance, CI/ES, Resolute Technique, reservation-heavy,
attribute-deficient, and locked-item constraints.

Run in a deployed/staging environment only:

```bash
php artisan lootwright:acceptance:poe1
```

The command prints only ruleset identity/checksum, parser version, finding and
recommendation codes, recipe count, and timings. It never persists or deletes
user data and refuses local/testing runtimes, fixture paths, missing rulesets,
checksum mismatches, edition mismatches, or non-canonical bindings.

## Cloud activation

Repository readiness is separate from Laravel Cloud runtime readiness. Import
the pinned official snapshot with the operator workflow, review the dry-run
output, publish/activate only after policy approval, then run the command above
against the Cloud deployment. If Cloud credentials, database, or queue
evidence are unavailable, status is `READY_FOR_CLOUD_ACTIVATION`, not live.

## Status vocabulary

- **PASS** — all canonical stages completed.
- **DEGRADED** — deterministic result completed with explicitly unsupported
  optional data.
- **BLOCKED** — fail-closed prerequisite (ruleset, policy, or Cloud runtime)
  is unavailable.
- **FAIL** — an invariant or canonical stage failed unexpectedly.
