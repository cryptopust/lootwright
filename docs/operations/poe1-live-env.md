# PoE1 live environment contract

This is the minimum Cloud configuration for the supported PoE1 local-build
vertical slice. Values are operator-managed; secrets are never committed or
printed.

| Name | Required state | Secret | Purpose / Cloud verification |
| --- | --- | --- | --- |
| `APP_ENV` | `production` | No | Production error handling. |
| `APP_DEBUG` | `false` | No | Prevents stack-trace disclosure. |
| `APP_URL` | Exact HTTPS Cloud URL | No | Canonical links and proxy origin. |
| `APP_RELEASE_SHA` | Deployed reviewed commit | No | Release identity. |
| `LOOTWRIGHT_RUNTIME_MODE` | `PRODUCTION_CANONICAL` | No | Fail-closed canonical runtime. |
| `DB_CONNECTION` | `pgsql` | No | Managed PostgreSQL. |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` | Cloud PostgreSQL values | Username/config | Database connection. |
| `DB_PASSWORD`, `DB_SSLROOTCERT` | Secret-managed TLS credentials | Yes | PostgreSQL authentication and verification. |
| `READINESS_TOKEN` | Random 32+ character value | Yes | Protects `/ready`. |
| `POLICY_GLOBAL_KILL_SWITCH` | `false` during an approved release; `true` for lockdown | No | Emergency deny-all override; never remove. |
| `IMPORTS_ENABLED` | `true` for PoE1 local import | No | Enables bounded user-submitted PoB intake only. |
| `RULESETS_ENABLED` | `true` after immutable PoE1 activation | No | Enables deterministic ruleset execution. |
| `EXTERNAL_LINKS_ENABLED` | `false` unless separately reviewed | No | Not required for analysis. |
| `OUTBOUND_NETWORK_ENABLED` | `false` | No | Local PoB parsing does not require egress. |
| `AUTH_REQUIRE_VERIFIED_EMAIL` | `true` for member production | No | Requires normal email verification. |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Reviewed working provider | Some are secret | Verification and password-reset delivery. |
| `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` | Cloud-supported shared drivers | No | Shared sessions/cache and asynchronous workflow. |
| `FILESYSTEM_DISK` / `analysis-artifacts` disk | Durable private object storage before async imports | Config/credentials | Prevents ephemeral Cloud filesystem loss. |
| `OPENAI_ENABLED`, `OPENAI_INTENT_ENABLED`, `OPENAI_EXPLANATIONS_ENABLED` | `false` | No | AI remains optional and disabled. |
| `POENINJA_ECONOMY_ENABLED`, `POEWIKI_IMPORT_ENABLED` | `false` | No | Market/Wiki providers remain disabled. |
| `POE_NINJA_ENABLED`, `GGG_PASSIVE_TREE_IMPORT_ENABLED` | `false` | No | Out-of-band source sync remains disabled. |
| `HORIZON_DASHBOARD_ENABLED`, `FUNDING_ENABLED` | `false` | No | Unrelated capabilities remain closed. |

## Capability boundary

| Capability | Required for PoE1 live | Default | Enable? |
| --- | --- | --- | --- |
| PoE1 local build import | Yes | Off in lockdown | Yes, after Cloud storage and policy seed gates pass |
| Local PoB parse/normalize | Yes | Off with import gate | Yes |
| Active immutable PoE1 ruleset read | Yes | Off in lockdown | Yes, after activation |
| Deterministic analysis | Yes | Off in lockdown | Yes |
| Planner | Yes | Local | Yes |
| Manual Trade recipe | Yes | Local | Yes |
| Remote build fetch / outbound network | No | Off | No |
| Market | No | Off | No |
| AI | No | Off | No |
| PoE2 | No | Off | No |

The HTTP import gate is intentionally separate from remote fetch. A missing
database policy rule for `USER-POB-001 / import / user_input.pob_code.import`
produces `policy_denied` with reason `missing_rule`; this is repaired by running
the reviewed `PolicyDefaultsSeeder` on the target Cloud database, not by
weakening the emergency kill switch or enabling outbound access.

## Operator verification

After migration, run the idempotent policy seed through the Cloud command
interface:

```bash
php artisan db:seed --class=PolicyDefaultsSeeder --force
```

Then use the ruleset candidate validation/publish/activation commands documented
in [`poe1-passive-tree-import.md`](poe1-passive-tree-import.md) and
[`ruleset-release.md`](ruleset-release.md). Keep the emergency global switch
available for rollback/lockdown.
