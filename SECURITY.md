# Security Policy

Lootwright is pre-alpha software. No release is currently supported for public
production use.

## Supported versions

| Version | Security support |
| --- | --- |
| Current `main` branch | Best-effort review during pre-alpha development |
| Tagged releases | None published |
| Public production service | Not available |

Security fixes are prepared against the current reviewed branch. Historical
commits are not maintained as supported releases.

## Report privately

Use GitHub's private vulnerability-reporting / Security Advisory flow for this
repository when it is enabled:
<https://github.com/cryptopust/lootwright/security/advisories/new>.

Private security contact:

> **[REPOSITORY OWNER: replace this placeholder with an authenticated private
> security contact before public staging.]**

If private reporting is unavailable, do not publish exploit details. Send only
a minimal request for a private channel to the verified repository owner and
wait until that channel is authenticated.

Never place any of the following in an issue, discussion, pull request, test
fixture, screenshot, or log excerpt:

- API keys, database URLs, passwords, access tokens, private keys, or cookies;
- `POESESSID`, Path of Exile credentials, or browser/session material;
- private PoB codes, build notes, prompts, emails, IP addresses, or user data;
- provider request/response bodies; or
- details that would enable live exploitation before a fix is available.

Use only original synthetic data in a reproduction. Never test against GGG,
Trade, OpenAI, another third party, or a public Lootwright environment without
explicit authorization. Do not probe undocumented endpoints.

## What to include

- affected commit SHA and environment;
- smallest original reproduction;
- expected and observed impact;
- whether authorization, deletion, parser, policy, egress, queue, ruleset, or
  deterministic integrity is involved; and
- any safe containment already performed.

## Maintainer response

Maintainers follow the [incident-response runbook](docs/security/incident-response.md):
triage privately, activate the narrowest emergency switches, preserve redacted
evidence, add a failing original-fixture regression, run the complete quality
gate, and publish a factual advisory when appropriate.

No response-time or disclosure deadline is promised until a named security
owner, hosting jurisdiction, and notification obligations are recorded.

This product isn't affiliated with or endorsed by Grinding Gear Games in any way.
