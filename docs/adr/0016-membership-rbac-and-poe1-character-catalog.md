# ADR 0016: Membership, RBAC, audit and the PoE1 character catalog

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

The PoE1 class catalog is immutable, version-controlled domain data for patch
3.28. It was verified on 2026-08-20 against the community-maintained Path of
Exile Wiki Ascendancy and 3.28 version pages. Seven base classes and twenty
normal Ascendancies are exposed. Normal Ascendancy and secondary progression
are distinct types; no unverified Bloodline data appears in the user flow.
Runtime code and migrations never fetch the Wiki.

Analysis ownership uses a nullable bigint `user_id` matching `users.id` while
the existing hashed privacy owner remains intact for deletion and workflow
compatibility. Raw PoB and item text never enter drafts, audit metadata,
Inertia props or browser storage.
