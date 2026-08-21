# External-source synchronization

Run `php artisan lootwright:sources:sync-poe-ninja` after setting
both `POE_NINJA_ENABLED=true` and `POENINJA_ECONOMY_ENABLED=true`, a non-empty
`POE_NINJA_CONTACT`, and seeding reviewed
policy defaults. Use `--league="<fetched league name>"` only for a league first
returned by the economy leagues endpoint. The command obtains an atomic cache
lock, fetches leagues first, normalizes configured categories transactionally,
and preserves existing snapshots on failure or HTTP 304.

`lootwright:sources:status` prints bounded non-sensitive run state.
`lootwright:sources:prune` keeps only current quotes and bounded operational
history, and clears completed normalized staging payloads after seven days while
retaining import reports. No command logs response bodies, cookies,
authorization values, or secrets.

Schedule `lootwright:sources:sync-poe-ninja` every 30 minutes in Laravel Cloud.
The source default refresh is 20 minutes and config cannot set it below five
minutes. No worker, Horizon or Valkey is required; a database cache lock is
sufficient.

## Manual imports

The `/admin/system` registry is an inspection surface for administrators. Only
a super-admin with 2FA and recent password confirmation can submit a manual
import, and only for a fixed adapter already reported as operational by both
configuration and Policy Gate state. A mandatory bounded reason is written to
the append-only admin audit log. The request queues
`RunExternalSourceImportJob` on `source-imports`; it never accepts a URL, path,
host, endpoint, league, category, credentials or environment value from the UI.
The controller fails closed when `QUEUE_CONNECTION=sync`; a database, Redis or
managed queue is required so an HTTP request cannot execute an import inline.
Run a database queue worker for manual UI imports:

```bash
php artisan queue:work database --queue=source-imports --tries=1 --timeout=600
```

The existing operator commands remain the preferred maintenance interface when
no worker is provisioned. A failed or quarantined import preserves the last
approved snapshot and current canonical data. Roll back canonical game data by
activating a previously published immutable ruleset; never delete or edit an
approved snapshot. Staging-only rollback is a Policy Gate operation and erases
the normalized staging payload while retaining its bounded report.

Each queued fixed-adapter run appends a checksum observation to
`source_update_observations`: `unchanged`, `changed_staged`, or `failed`.
`changed_staged` means new immutable content was observed; it does not mean the
dataset was published or activated. Review import reports, validation,
conflicts, provenance, and coverage before explicitly publishing a new ruleset.
Never schedule automatic activation.

## Operational matrix

| Adapter | State | Boundary |
| --- | --- | --- |
| Official GGG PoE1 passive tree | default-off, operational-capable | Exact approved commit/checksum and raw GitHub URL or local operator file only |
| poe.ninja PoE1 economy | default-off, conditional operational-capable | Documented leagues/exchange/stash overview endpoints only |
| Official documented GGG APIs | disabled | Registration, credentials, scopes and each exact operation require review |
| PoE Wiki Cargo | disabled | Permission, underlying rights, commercial use, cache and redistribution review pending |
| PoE2 canonical datasets | disabled/unavailable | No dataset has passed Policy and Provenance review |
| Official Atlas tree | disabled/outside MVP | Contract recorded; no active importer |
| RePoE | prohibited | No runtime adapter or HTTP client |
| Trade search/fetch/data, scraping, POESESSID | prohibited | No adapter and no override |
