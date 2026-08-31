<?php

return [
    // Opt-in in staging/production so profiling never adds request overhead by default.
    'enabled' => (bool) env('PERFORMANCE_TELEMETRY_ENABLED', false),
    'slow_query_ms' => (int) env('PERFORMANCE_SLOW_QUERY_MS', 100),
    'ruleset_cache_seconds' => (int) env('RULESET_CACHE_SECONDS', 3600),
    'canonical_cache_seconds' => (int) env('CANONICAL_CACHE_SECONDS', 3600),
];
