<?php

$local = env('APP_ENV', 'production') !== 'production';

$csv = static function (string $name, string $default = ''): array {
    return array_values(array_filter(
        array_map('trim', explode(',', (string) env($name, $default))),
        static fn (string $value): bool => $value !== '',
    ));
};

return [
    'lockdown' => (bool) env('DEPLOYMENT_LOCKDOWN_MODE', true),
    'release_sha' => env('APP_RELEASE_SHA'),
    'trusted_hosts' => $csv(
        'TRUSTED_HOSTS',
        $local ? '^localhost$,^127\\.0\\.0\\.1$,^lootwright\\.test$' : '',
    ),
    'trusted_proxies' => $csv(
        'TRUSTED_PROXIES',
        $local ? '127.0.0.1' : '',
    ),
];
