# Laravel Cloud CLI operations

Lootwright operators use the official Laravel Cloud CLI as the runtime
interface for the deployed Cloud environment. It is deliberately installed
globally and is not a Lootwright application dependency.

## Installation

```powershell
composer global require laravel/cloud-cli
```

The executable is `cloud` (on Windows the Composer shim is `cloud.bat`), not
the Herd Laravel Installer command `laravel cloud`. Ensure Composer's global
bin directory is on the current operator session's `PATH`:

```powershell
$env:PATH = "$(composer global config bin-dir --absolute);$env:PATH"
cloud --version
```

## Authentication

Authenticate with the CLI's browser flow when needed:

```powershell
cloud auth
```

For automation, use the CLI-supported token flow (`cloud auth:token --add`)
and store the token only in the operator's secure credential store. Never put
tokens in the repository, `.env` files, logs, screenshots, or reports.

## Discover and bind the project

From the repository root, inspect `.cloud/config.json` and use read-only
listing commands to identify the application and environment. Lootwright's
production environment must be positively matched by hostname before any
remote command is run:

```powershell
cloud application:list --json --no-ansi
cloud environment:get <environment-id> --json --no-ansi
cloud deployment:list <environment-id> --json --no-ansi
```

The committed `.cloud/config.json` contains only the non-secret organization
and application identifiers. Do not infer an environment from its name when
more than one environment exists; verify the URL/vanity hostname.

## Safe remote Artisan commands

Run commands inside the selected Cloud environment with `command:run`:

```powershell
cloud command:run <environment-id> \
  --cmd="php artisan about --only=environment --no-ansi" \
  --json --no-ansi
cloud command:run <environment-id> \
  --cmd="php artisan migrate:status --no-ansi" \
  --json --no-ansi
```

Use this interface for production Artisan commands so the output is evidence
from Cloud rather than from a local checkout. Avoid commands that print
environment secrets or raw user/build payloads.

## Logs and resources

Fetch a bounded, relevant log window and avoid unrelated user data:

```powershell
cloud environment:logs <application-id> <environment-name> \
  --minutes=10 --tail=30 --json --no-ansi
```

Inventory resources without creating or changing them:

```powershell
cloud database-cluster:list --json --no-ansi
cloud cache:list --json --no-ansi
cloud bucket:list --json --no-ansi
cloud instance:list <environment-id> --json --no-ansi
```

The CLI may report provider-specific limitations (for example, an unsupported
database-cluster include); use a successful remote `php artisan db:show` as
the PostgreSQL runtime proof and record the limitation.

## Deployment inspection

```powershell
cloud deployment:get <deployment-id> --json --no-ansi
cloud deploy:monitor <application-id> <environment-name> --json --no-ansi
```

Record the immutable commit hash and deployment status before relying on
runtime behavior. Deployments and production data mutations require a
separate, reviewed operation; this document covers operator access and
read-only verification only.

