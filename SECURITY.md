# Security Policy

Lootwright is pre-release software. No version is currently designated as a
supported production release, and the repository must not be described as
production-ready while the blockers in `docs/release/mvp-readiness.md` remain.

## Reporting a vulnerability

Do not include exploit details, credentials, private builds, prompts, or personal
data in a public issue. Use the repository host's private vulnerability-reporting
or Security Advisory channel when it is enabled. If no verified private channel
is available, send only a minimal request for a private contact to the verified
maintainer identity published by the repository operator; do not send the
sensitive report until that channel is authenticated.

A verified security contact and response owner are deployment prerequisites.
Their current absence is recorded as a release blocker rather than replaced by
an invented email address.

Include, without real user data:

- the affected release SHA and ruleset/parser identity;
- the smallest original reproduction;
- expected and observed security impact;
- whether authorization, deletion, policy, egress, parser, or deterministic
  integrity is involved; and
- any safe containment already performed.

Never test against GGG, Trade, OpenAI, another third party, or a public
Lootwright deployment without explicit authorization. Do not probe undocumented
endpoints or use live player credentials.

## Maintainer response

Maintainers follow `docs/security/incident-response.md`: privately triage,
activate the narrowest emergency switches, preserve redacted evidence, add a
failing original-fixture regression test, run the full security and quality
gates, and publish a factual advisory when appropriate. Notification timelines
depend on the deployment jurisdiction and must be set before public hosting.

This product isn't affiliated with or endorsed by Grinding Gear Games in any way.
