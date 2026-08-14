# PoB Build Import Compatibility

Status: PoE1 MVP import supported; PoE2 format intake is beta. Reviewed
2026-08-14 against the pinned records in the [source register](../compliance/source-register.md).

## Accepted input

Lootwright accepts only content a user deliberately pastes or uploads:

- a PoB/PoB2 Base64 or Base64URL share code;
- an uploaded `text/plain` share-code file; or
- already-decompressed XML pasted as text.

HTTP and HTTPS input is rejected. A `pobb.in` URL is recognized only to explain
that the raw share code must be pasted. Lootwright does not request that URL or
any URL discovered inside the build.

The local diagnostic command uses the same framework-independent parser and
does not access PostgreSQL, Redis, the network, or OpenAI:

```powershell
php artisan pob:import-fixture tests/Fixtures/Pob/poe1-minimal.xml
```

The command rejects URL/stream-wrapper syntax and UNC network-share paths
before any filesystem call, then resolves and reads only a regular local file.
Its database-free in-memory gate denies unknown tuples, enforces the evidence
effective period, and honors `POLICY_GLOBAL_KILL_SWITCH`; hosted HTTP execution
also honors persisted source/capability switches through the database gate.

## Security limits

The current hard limits are 1 MiB request text, 768 KiB compressed data, 4 MiB
decoded XML, a 64:1 expansion ratio, XML depth 32, 20,000 elements, 64
attributes per element, 4,096 allocated passive nodes, 256 skill groups, 2,048
gems, 512 items, and 64 KiB for each bounded notes or item-text block. Uploaded
files must be plain text and no larger than 1 MiB. Decode, XML parse, and
normalization share a 2,000 ms monotonic processing budget. Count or time-limit
overflow rejects the import instead of returning a partial build.

Base64 decoding is strict and decompression has an explicit output ceiling. XML
parsing rejects DTD and entity declarations, disables external entities,
substitution, DTD loading, and network access, and enforces UTF-8, nesting,
element, and attribute limits. The importer never evaluates Lua, embedded
scripts, macros, shell commands, HTML, or other executable content.

## Edition evidence and normalized fields

The exact root element proves the adapter: `PathOfBuilding` selects PoE1 and
`PathOfBuilding2` selects PoE2. Missing, unknown, or conflicting markers are
rejected; the importer never guesses or falls back to the other game.

Where present, the importer normalizes build version text, character level,
class and ascendancy identifiers, bandit/pantheon choices, allocated passive
node identifiers, skill groups, gems, levels, quality, enabled state, sockets
and link groups, equipment slots, item text, configuration values, calculated
summary values, and notes. Notes and item text remain separately labelled
untrusted text. Unknown XML elements are retained as explicit unsupported
feature records with bounded attributes instead of disappearing silently.

The output includes warnings, unsupported features, parser version, normalized
input SHA-256, source commit, license checksum, and attribution provenance.
All identifiers at this stage are parser-local opaque identifiers prefixed with
`poe1` or `poe2`; identical upstream values therefore remain edition-isolated.
PoE1-only choice attributes are not consumed by the PoE2 adapter.

## Analysis boundary and PoE2 beta limits

The result is a `CanonicalImportedBuild`: a deterministic pre-ruleset intake
document, not the analysis-grade `CanonicalBuild`. A PoB `targetVersion` value
does not prove a Lootwright patch or immutable ruleset. Analysis remains
disabled until an exact same-edition patch, league where relevant, parser, and
approved ruleset can be resolved. No game formulas or datasets are included.

PoE2 uses the shared import port but a separate parser and normalizer. It is
beta because only small Lootwright-original structural fixtures establish
compatibility; no parity with all PoB2 features or activation of PoE2 analysis
is claimed.

## Privacy and retention

Transient import is the default. Raw codes and raw XML are never written to the
database. Logs contain only SHA-256 request hashes and coarse outcomes, never
the submitted content. Imported notes and arbitrary prose are not sent to an AI
provider, and no AI provider is enabled.

With an authenticated Lootwright owner, explicit storage consent, and a
high-entropy 32-128 character idempotency key, only the normalized JSON is
encrypted using the application key. The default lifetime is 24 hours and the
configurable maximum is 168 hours. Owner and idempotency identifiers are stored
only as keyed hashes. An application-key-derived deletion capability is
returned on creation and exact idempotent replay; only its SHA-256 hash is
stored. Reusing a key for different input fails with a conflict. Users can
delete the record through the deletion endpoint, and an hourly command prunes
expired records. Private responses use `Cache-Control: no-store`.

No public account/login flow exists yet. This intentionally leaves hosted
persistence unreachable to anonymous callers while transient import remains
usable. Lootwright sessions are unrelated to GGG and never accept GGG cookies,
credentials, or `POESESSID`.

See the [Path of Building attribution](../compliance/path-of-building-attribution.md)
for upstream version and license details. Test fixtures contain only invented,
Lootwright-original structure and identifiers.
