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
history. No command logs response bodies, cookies, authorization values, or
secrets.

Schedule `lootwright:sources:sync-poe-ninja` every 30 minutes in Laravel Cloud.
The source default refresh is 20 minutes and config cannot set it below five
minutes. No worker, Horizon or Valkey is required; a database cache lock is
sufficient.
