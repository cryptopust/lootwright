<?php

return [
    'raw_artifact_ttl_minutes' => (int) env('ANALYSIS_RAW_ARTIFACT_TTL_MINUTES', 60),
    'runtime_mode' => env(
        'LOOTWRIGHT_RUNTIME_MODE',
        in_array(env('APP_ENV', 'production'), ['local', 'testing'], true) ? 'TEST_FIXTURE' : 'PRODUCTION_CANONICAL',
    ),
];
