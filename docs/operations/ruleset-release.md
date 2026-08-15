# Ruleset Import and Activation Runbook

Status: blocked. No production PoE1 or PoE2 ruleset source, importer, catalog adapter,
or activation command exists. `POE1-RULES-001` and `POE2-RULES-001` remain disabled in
the source register, so release operators must not invent an artifact or activate a
placeholder.

## Preconditions for a future implementation

Before code may import anything, a reviewed change must provide:

1. an active source-register record with exact canonical URL/offline origin, owner,
   version, retrieval date, SHA-256, license/permission, commercial/derivative and
   redistribution analysis, retention, attribution, and review expiry;
2. an accepted ADR for the source and activation boundary, including PoE1/PoE2
   isolation and rollback;
3. an offline, content-addressed package schema carrying edition, patch, league,
   source version, parser version, ruleset version, and checksum;
4. a deny-by-default Policy Gate operation for import, validation, publication, and
   activation; environment flags cannot supply missing permission;
5. original tiny tests for signature/checksum failure, unknown/expired source,
   duplicate immutable publication, stale activation, cross-edition identifiers,
   rollback, and deterministic replay;
6. a protected operator command that stages and validates a local file without URL
   fetching, logging content, or mutating a published version.

## Required staged flow

When those prerequisites exist, the operator will:

1. obtain the exact approved offline artifact through the recorded source process;
2. verify transport/source checksum and scan for executable code, archives, GGG art,
   logos, item images, flavour text, fonts, full builds, and unapproved datasets;
3. import into quarantine under a transaction using the separate operator identity;
4. validate schema, signature/checksum, provenance/evidence period, edition/patch/
   league/parser compatibility, canonical vocabulary, uniqueness, and cross-game
   isolation;
5. run unit, fixture, boundary/property, checksum, deterministic replay, Trade trace,
   Policy Gate denial-first, and fast/extended eval suites against the staged identity;
6. have a second reviewer compare the manifest, source evidence, diff, counts, and
   hashes without rubber-stamping generated changes;
7. publish an immutable record; publication creates a new version and never edits an
   existing one;
8. activate the exact checksum with a dated audit record during a release window, then
   restart workers and prove queued jobs reject stale identities;
9. monitor typed unsupported/refusal rates and deterministic hashes without inspecting
   user content.

Activation failure or ambiguity leaves the prior exact ruleset active or, when none is
approved, keeps analysis unavailable. There is no “latest” fallback. Rollback is a new
audited activation pointing to a previously published immutable checksum; it never
deletes or rewrites the rejected version. A compromised or revoked source triggers the
global/source/capability kill switch and incident response.

Until a later reviewed implementation updates this runbook with real command names and
tests, `RULESETS_ENABLED=false` is the only production packaging default and the
deterministic engine continues to fail closed.

