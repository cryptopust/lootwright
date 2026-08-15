<?php

return [
    'proxies' => array_values(array_filter(
        array_map('trim', explode(',', (string) env(
            'TRUSTED_PROXIES',
            env('APP_ENV', 'production') !== 'production' ? '127.0.0.1' : '',
        ))),
        static fn (string $value): bool => $value !== '',
    )),
];
