# Security Baseline

Status: implemented application baseline, reviewed 2026-08-15. This baseline is
mandatory for every environment. The stricter production values in the
[deployment checklist](deployment-security-checklist.md) are release gates.

## Identity and authorization

Lootwright has no public registration or login routes yet. Laravel's session
guard and `User` model are ready for accounts, passwords use the framework's
hashed cast, production password policy requires 12 mixed characters, and the
password-confirmation window is 15 minutes. Email verification is available
through `MustVerifyEmail` and becomes mandatory for account API access only
when `AUTH_REQUIRE_VERIFIED_EMAIL=true`. Anonymous persistence uses a random
256-bit privacy-session secret stored only as SHA-256, never an IP, user agent,
cookie fingerprint, or browser fingerprint.

Every analysis query, comparison, export, reanalysis, build deletion, and
principal deletion resolves an account or privacy-session principal and passes
that owner identity into an owner-scoped repository query. A missing principal
returns 401; a different owner receives the same 404 as a missing resource.
Authorization never depends on a client-supplied owner ID.

Policy administration is a separate, default-off operator boundary. It
requires `POLICY_ADMIN_ENABLED=true`, a minimum 32-character environment-only
token, CSRF, and the `policy-admin` limiter. Account or privacy-session
credentials grant no admin authority. A privacy credential is rejected on the
admin route even when an admin token is also present. Horizon remains denied
outside local development.

Session records are encrypted and JSON-serialized. Cookies are HttpOnly,
SameSite=Lax, and Secure by default in production. Authentication handlers must
regenerate the session ID after login and invalidate the session plus CSRF token
on logout before any public account routes are added.

## Browser and HTTP boundary

All Laravel responses pass through the security-header middleware. Production
uses a CSP with `default-src 'self'`, no inline or evaluated scripts,
`object-src 'none'`, `frame-ancestors 'none'`, same-origin forms, and explicit
connect/image/font/worker rules. Local Vite origins and inline development CSS
are permitted only in local/testing environments.

The middleware also sets HSTS on production HTTPS responses, `DENY`
clickjacking protection, MIME sniffing denial, no-referrer policy, restrictive
permissions policy, same-origin opener isolation, same-site resource policy,
and `X-XSS-Protection: 0`. API responses default to private no-store caching.
Laravel's web middleware supplies CSRF protection to every state-changing
route. JSON and Inertia rendering escape hostile text; no component uses
`v-html`, and AI output is plain text rather than HTML or Markdown.

Requests do not accept return URLs or perform user-controlled redirects.
Exports use fixed UUID-derived filenames, `application/json`, no-store caching,
and a content hash. Evidence URLs are data only, are never fetched, and admin
validation accepts HTTPS URLs only on the documented host allowlist with no
credentials or non-standard port.

## Parser boundary

PoB input is capped at 1 MiB before orchestration. The parser caps compressed
bytes, expanded XML bytes, decompression ratio, XML depth, element and attribute
counts, passive nodes, skills, gems, items, text fields, and total monotonic
processing time. Base64 is strict. XML must be UTF-8, and DTD, entity,
XInclude-style external resolution, and network access are denied. Libxml uses
`LIBXML_NONET`, external resolution and entity substitution are disabled, and
unknown or invalid values stay typed as unknown or are rejected.

URLs found in input are never fetched. Raw queued artifacts are application-key
encrypted in private storage, referenced by opaque key only, removed after
parse or terminal rejection, and pruned after a one-hour ceiling.

## Egress and AI

Outbound networking is disabled unless `OUTBOUND_NETWORK_ENABLED=true`.
Every future outbound adapter must use the central guard with an exact operation,
scheme, hostname, port, and path. Queries, fragments, userinfo, redirects,
missing DNS, and any private, loopback, link-local, multicast, reserved, or
otherwise non-public DNS answer are denied. The only registered MVP target is
`POST https://api.openai.com/v1/responses`; the HTTP client does not follow
redirects.

AI additionally requires explicit opt-in, `OPENAI_ENABLED=true`, an exact
Policy Gate allow, token and spend budgets, and a configured key. It receives
minimum typed context, no PoB, secrets, session data, complete private notes,
tools, or browsing capability. Prompt-injection markers fail to deterministic
forms. Strict schemas, closed ruleset vocabulary, exact deterministic code and
order checks, one repair attempt, and forbidden HTML/URL/price/Trade content
checks prevent provider authority expansion.

## Rate limits

Rate keys are HMACs of the account ID, privacy credential, or request IP. Raw
identifiers and IPs are not placed in cache keys.

| Boundary | Per minute | Per day |
| --- | ---: | ---: |
| Authentication foundation | 5 | 25 |
| Anonymous privacy-session creation | 3 | 20 |
| PoB imports | 10 | 100 |
| Analysis submission/reanalysis | 6 | 60 |
| Analysis reads/comparison/provenance | 60 | 1,000 |
| AI foundation | 5 | 50 |
| Export | 5 | 30 |
| Deletion | 3 | 10 |
| Public policy explanations | 30 | 300 |
| Policy administration | 10 | 50 |

AI spend budgets are additional to request rate limits. Queue work is bounded,
edition-scoped, uniquely keyed, and retried only for typed transient failures.
Jobs validate UUIDv7, edition, and exact lowercase ruleset checksum before
claiming work. Invalid payloads, missing identities, stale selections, policy
denials, and emergency switches are terminal.

## Secrets, logs, and telemetry

Secrets live only in deployment secret storage and environment variables.
`.env`, provider keys, database/Redis passwords, readiness/admin tokens, and
anonymous credentials must never enter source, screenshots, tickets, queue
payloads, or telemetry. The log processor recursively removes credential,
cookie, password, token, API-key, raw artifact, PoB, prompt, session, and private
note fields; it also scrubs bearer/OpenAI/privacy credential patterns and caps
strings. Application logs use opaque hashes, IDs, exception types, and coarse
outcomes.

PostgreSQL and Redis are loopback-only in local Compose and must have no public
listener in production. Local Compose creates a non-superuser PostgreSQL
application role and requires Redis authentication. Production requires a
separate migration role, TLS verification, Redis ACL/TLS, encrypted backups,
and tested restores.

## Emergency controls

- `POLICY_GLOBAL_KILL_SWITCH=true` denies every Policy Gate capability.
- `IMPORTS_ENABLED=false` stops HTTP, queued, and direct PoB orchestration.
- `RULESETS_ENABLED=false` blocks reanalysis and queued ruleset execution.
- `OPENAI_ENABLED=false` stops AI before policy, budget, or network access.
- `OUTBOUND_NETWORK_ENABLED=false` denies all registered egress.
- `EXTERNAL_LINKS_ENABLED=false` removes the Trade homepage action and blocks
  recipe link policy.
- Funding is code-disabled and cannot be enabled by environment configuration.

Activation or recovery follows the [incident response runbook](incident-response.md).
