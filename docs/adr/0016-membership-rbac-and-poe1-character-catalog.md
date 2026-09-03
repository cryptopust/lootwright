# ADR 0016: Membership, RBAC, audit and the dual-game character catalog

Status: accepted, 2026-08-20.

Lootwright uses Laravel Fortify as the official backend authentication layer and
custom Inertia/Vue pages matching the existing Forge Ledger product design.
Roles and account states are portable database strings cast to backed enums.
Policies, middleware and controller authorization enforce ownership and admin
boundaries; hiding a Vue action is never an authorization decision.

Admin changes require recent password confirmation and append an immutable,
redacted audit record. Admin and super-admin accounts must confirm Fortify 2FA
before entering the session-based admin panel. The existing token-protected
`policy.admin` operations path remains separate and never exposes its token to
Inertia.

The character catalog is immutable, version-controlled, and game-scoped. PoE1
patch 3.28 exposes seven classes and twenty regular Ascendancies. PoE2 Early
Access version 0.5 exposes twelve planned classes, eight available classes,
twenty-two regular available Ascendancies, and the Witch/Lich-only alternate
Abyssal Lich. Planned classes are visible as unavailable metadata but cannot be
submitted. Normal, alternate, and secondary progression are distinct types.
Sources and verification timestamps are part of each catalog response; runtime
code and migrations never fetch a wiki.

Existing `analyses.game_edition` remains the canonical persisted game identity.
Drafts receive the same portable string column in a forward migration. Drafts
created before dual-game intake can be identified safely as `poe1`, because the
only selectable wizard edition at that time was PoE1. Catalog data remains in
version-controlled immutable PHP definitions rather than database rows, so
`game + slug` identity is enforced by edition-scoped identifiers without a
second mutable source of truth.

Analysis ownership uses a nullable bigint `user_id` matching `users.id` while
the existing hashed privacy owner remains intact for deletion and workflow
compatibility. Raw PoB and item text never enter drafts, audit metadata,
Inertia props or browser storage.
