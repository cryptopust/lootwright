# Pull Request

## Summary

Describe the concrete problem and the smallest coherent change.

## Boundaries and evidence

- [ ] I read `AGENTS.md` and the relevant ADRs.
- [ ] I did not add undocumented GGG endpoints, scraping, automation,
      `POESESSID` handling, protected assets, live listings, or fabricated data.
- [ ] Any source/ruleset change includes exact permission, provenance, version,
      parser/game scope, and checksum evidence.
- [ ] Deterministic domain code remains independent of Laravel and providers.
- [ ] PoE1 and PoE2 identity boundaries remain explicit.

## Verification

List exact commands and results. Mark anything not run and explain why.

```text
composer validate --strict
composer audit
composer run format:check
composer run analyse
composer run test
npm ci
npm audit --audit-level=high
npm run lint
npm run typecheck
npm run test
npm run build
```

## Risk and operations

- Migrations/rollback:
- Security/privacy:
- Policy/provenance:
- Queue/cache/storage:
- Documentation:
- Remaining limitations:

## Data hygiene

- [ ] This change contains no credentials, private builds, prompts, user data,
      logs, dumps, generated dependencies, build output, or publisher assets.
- [ ] Tests and screenshots use only original synthetic fixtures.
