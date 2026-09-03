# External data sources

External data is an application concern. `src/Application/ExternalSources`
contains immutable DTOs and ports only; Laravel HTTP, cache, database and policy
implementations stay in `app/Modules/ExternalSources`.

Two adapters are operational-capable, and both remain default-off:

- `GGG-POE1-SKILLTREE-001` accepts only the reviewed commit-pinned official
  export through its operator workflow; and
- `POENINJA-ECONOMY-001` accepts only the documented PoE1 economy API surface.

The poe.ninja adapter remains off
until both `POE_NINJA_ENABLED=true` and `POENINJA_ECONOMY_ENABLED=true`, an
operator contact is configured, and the
database Policy and Provenance Gate allows the exact operation. It calls only
the documented PoE1 economy leagues, exchange-overview and stash-item-overview
endpoints. URL, host, scheme and category construction is configuration-owned;
no request accepts a user-controlled URL.

Normalized `PriceEvidence` carries source, version, PoE edition, league,
category, identifier, display name, value/currency, timestamps, freshness, and
payload checksum. It is immutable evidence. AI may explain it but cannot create,
replace or complete it. Icons and raw responses are not persisted.

Snapshots are fresh until `expires_at`, `stale_usable` through the configured
stale window, then expired; absence is unavailable. A temporary failure keeps a
last valid snapshot, but expired values must not be used as current.

PoE Wiki Cargo, RePoE, remote pobb.in and GGG OAuth adapters are disabled,
review-gated candidates. The GGG Currency Exchange interface is limited to
historical hourly aggregates. Public Stash indexing is deferred: it needs
`service:psapi`, has documented delay, and violates the MVP cost and
full-market-indexing boundary.

## Registry and staged imports

`policy_data_sources` is the sole Source Registry. Its records carry reference,
documentation and terms URLs; edition scope; allowed and forbidden exact
capabilities; redistribution, commercial-use and cache/storage decisions;
policy-review evidence; configuration state; and emergency kill-switch state.
An adapter's presence never grants authority. `ExternalSourceAdapterCatalog`
contains only fixed source codes and reports `operational` or a bounded disabled
reason. Disabled adapters have no HTTP dependency.

Remote normalized records enter `source_import_staging_records` through a
Policy Gate decision before an immutable `source_snapshots` row can be cited.
`source_import_reports` records source/version/edition, target, raw and
normalized checksums, received/imported/rejected counts and approval status.
Raw bodies are not stored in staging. A content-derived identity makes replay
idempotent under concurrent imports. Approval requires a valid same-source,
same-edition immutable snapshot. Staging rollback is separately policy-gated,
clears normalized staging payloads, and cannot mutate approved snapshots or
published canonical rulesets; canonical rollback remains activation of a prior
immutable ruleset.

The GGG passive-tree importer and poe.ninja quote synchronization both pass
through staging. No normal user request dispatches an import. The admin action
accepts only a fixed registry source code and mandatory reason, requires an
active verified super-admin with 2FA and recent password confirmation, is rate
limited, audited, queued on `source-imports`, and protected by an atomic cache
lock.

## Canonical normalization and authority

Game-data snapshots pass through separate PoE1 and PoE2 normalizers. A shared
envelope does not imply a shared upstream schema. Canonical assembly queries the
edition/category/source authority registry and fails closed when no enabled
authority exists. Category precedence is deterministic, but contradictory facts
are written to the append-only conflict quarantine and no winner is selected.

Successful fixed-adapter jobs compare the newest valid snapshot checksum with
the pre-run checksum and append an `unchanged` or `changed_staged` observation;
failures append a bounded failure code. These observations are detection
evidence only. They cannot publish or activate a ruleset. See the [data-quality
report](../data/data-quality.md).
