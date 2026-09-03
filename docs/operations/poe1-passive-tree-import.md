# PoE1 Passive-Tree Import

Status: operator-only, default-off production procedure.

Lootwright imports only the root `data.json` from Grinding Gear Games' official
[`grindinggear/skilltree-export`](https://github.com/grindinggear/skilltree-export)
repository. The reviewed revision is
`8bd138b32ea2631455cac5935bfab089f826094f` (`3.29.1`); its raw Git content
SHA-256 is
`7e9f755e33152129ebf36c2ebdad639c527e4ad70d274b1fefb860f30ca01122`.
Neither `master`, another branch name, an unreviewed commit, another filename,
nor repository assets are accepted.
The repository exposes no separate license file; snapshot metadata therefore
uses `LicenseRef-GGG-Terms-of-Use` and this narrow approval does not authorize
republishing the upstream dataset or its assets.

This command is an operations boundary, never a web/request-time dependency:

```bash
php artisan poe:import-passive-tree --file=/absolute/path/to/data.json --dry-run
php artisan poe:import-passive-tree --file=/absolute/path/to/data.json
php artisan poe:import-passive-tree --url=https://raw.githubusercontent.com/grindinggear/skilltree-export/8bd138b32ea2631455cac5935bfab089f826094f/data.json --dry-run
php artisan poe:import-passive-tree --url=https://raw.githubusercontent.com/grindinggear/skilltree-export/8bd138b32ea2631455cac5935bfab089f826094f/data.json --activate
```

## Preconditions

1. Deploy and migrate the reviewed release. Seed the exact current Policy Gate
   defaults with `php artisan db:seed --class=PolicyDefaultsSeeder --force`.
2. Set `GGG_PASSIVE_TREE_IMPORT_ENABLED=true`. For URL mode, also set a real
   operator contact in `GGG_PASSIVE_TREE_CONTACT` and temporarily set
   `OUTBOUND_NETWORK_ENABLED=true`. Never commit the contact if it is private.
3. Ensure `IMPORTS_ENABLED=true`, and for `--activate`,
   `RULESETS_ENABLED=true`. Global/source/capability kill switches must permit
   the exact operation.
4. Use `--dry-run` first. It validates the checksum and full bounded schema,
   creates no source/ruleset lifecycle row, and cannot be combined with
   `--activate`. The deny-by-default Policy Gate may append its normal
   non-sensitive authorization audit.

File mode accepts only a regular absolute local file no larger than 8 MB. It
identifies the approved upstream revision by exact raw-content checksum. URL
mode accepts only HTTPS, `raw.githubusercontent.com`, the exact
`grindinggear/skilltree-export/{40-char-commit}/data.json` path, no query,
userinfo, custom port, fragment, redirect, private/reserved DNS result, or
unapproved commit. Requests send `Accept: application/json`, a configured
Lootwright version/contact User-Agent, and short timeouts.
The exact `live_fetch: ggg.poe1.skilltree.export.fetch` Policy Gate decision is
evaluated before the HTTP client runs; import/quarantine authorization is a
separate decision after acquisition.

## Persistence and activation

Validation normalizes node identity/name/type, keystone/notable/mastery flags,
stats, directed connections, class/Ascendancy relation, secondary progression,
mastery effects, and the icon path as a text reference only. Images and sprites
are never downloaded. Flavour text and layout/rendering data are discarded.

Every attempt writes `external_source_sync_runs`. A valid import writes an
immutable `source_snapshots` row with both the raw upstream checksum and the
canonical normalized snapshot checksum. The same upstream checksum replays the
existing row. Invalid JSON/schema or checksum mismatch creates an immutable
`rejected` metadata snapshot and `source_conflicts` quarantine entry without
retaining the raw response body. It cannot be published or activated.

`--activate` publishes the normalized snapshot as an immutable
`ruleset_versions` row and changes `ruleset_activations` plus append-only
history in the existing database transaction. A validation, publication, or
Policy Gate failure leaves the prior active ruleset untouched. Activation does
not enable the production analyzer; that remains a later release gate.

After the window, set `OUTBOUND_NETWORK_ENABLED=false` and
`GGG_PASSIVE_TREE_IMPORT_ENABLED=false`. Review the non-sensitive command
output, sync run, quarantine queue, activation history, and active exact scope.

Required notice: This product isn't affiliated with or endorsed by Grinding
Gear Games in any way.
