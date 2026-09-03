$ErrorActionPreference = 'Stop'

Write-Host 'Running Laravel Cloud predeploy checks...'
php artisan deploy:check-cloud-config --no-ansi
php artisan migrate:status --no-ansi
composer run cloud:preflight
Write-Host 'Predeploy checks passed.'
