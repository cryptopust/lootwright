$ErrorActionPreference = 'Stop'

Write-Host 'Running Laravel Cloud postdeploy checks...'
$baseUrl = [Environment]::GetEnvironmentVariable('APP_URL')
if ([string]::IsNullOrWhiteSpace($baseUrl) -or -not $baseUrl.StartsWith('https://')) {
    throw 'APP_URL must be an HTTPS Laravel Cloud URL.'
}

$up = Invoke-WebRequest -Uri ($baseUrl.TrimEnd('/') + '/up') -UseBasicParsing -TimeoutSec 20
if ($up.StatusCode -ne 200 -or $up.Content.Trim() -ne 'OK') { throw '/up health check failed.' }

php artisan migrate:status --no-ansi
php artisan queue:failed --no-ansi
php artisan lootwright:sources:status --no-ansi

$token = [Environment]::GetEnvironmentVariable('READINESS_TOKEN')
if ([string]::IsNullOrWhiteSpace($token)) { throw 'READINESS_TOKEN is required for readiness verification.' }
$ready = Invoke-WebRequest -Uri ($baseUrl.TrimEnd('/') + '/ready?detail=1') -Headers @{ 'X-Lootwright-Readiness-Token' = $token } -UseBasicParsing -TimeoutSec 20
if ($ready.StatusCode -ne 200) { throw 'Detailed readiness check failed.' }

Write-Host 'Postdeploy checks passed.'
