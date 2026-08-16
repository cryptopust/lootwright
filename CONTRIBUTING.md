# Contributing to Lootwright

Lootwright welcomes issue reports and review feedback, but it is not yet ready
to merge outside code contributions. Contributor licensing and trademark
governance must first be approved and recorded; opening the source under MIT
does not authorize a maintainer to accept code, data, or assets whose rights are
unclear.

## Before proposing a change

Read `AGENTS.md`, `LICENSE-SCOPE.md`, the relevant ADRs, and the source register.
If implementation and a governing document disagree, update and review the
governing decision before changing code.

Never submit:

- GGG or Path of Exile art, logos, item icons, screenshots, fonts, flavour text,
  datasets, or copied interface material;
- player builds, private notes, credentials, cookies, `POESESSID`, prompts, or
  other personal data;
- scraped material, undocumented Trade endpoint behavior, encoded Trade URLs,
  market listings, prices, browser/client automation, or game-client access;
- generated credentials, `.env`, dependency directories, build output, dumps,
  or live-provider recordings; or
- code or data without verified provenance and permission for the exact use.

Tiny fixtures must be original and structurally minimal. Private authorized
fixtures stay under the ignored `evals/private/` boundary and are never used to
update a committed baseline.

## Engineering workflow

Keep the deterministic domain and application ports under `src/` independent of
Laravel, Eloquent, HTTP, queues, storage, and provider SDKs. Keep PoE1 and PoE2
identities isolated. Add denial-path tests before success-path integration tests,
and update an ADR when architecture, policy, source, funding, or deterministic
behavior changes.

Run the exact quality gate in `AGENTS.md`. A change description must identify
tests actually run, source/provenance effects, privacy or security impact,
migrations, and residual limitations. Use a small Conventional Commit message;
do not commit or deploy on behalf of another person without authorization.

## Contribution acceptance blocker

Before the first external pull request is merged, maintainers must publish an
approved inbound-contribution policy, verified maintainer/security contacts, and
trademark governance. Until then, external patches may be discussed and reviewed
for learning, but they must not be represented as accepted project contributions.

This product isn't affiliated with or endorsed by Grinding Gear Games in any way.
