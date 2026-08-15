<?php

return [
    'enabled' => (bool) env('OPENAI_ENABLED', false),
    'provider' => 'openai',
    'api_key' => env('OPENAI_API_KEY'),
    'intent_model' => env('OPENAI_INTENT_MODEL', 'gpt-5.4-nano'),
    'explanation_model' => env('OPENAI_EXPLANATION_MODEL', 'gpt-5.4-nano'),
    'prompt_template_version' => env('OPENAI_PROMPT_TEMPLATE_VERSION', '2026-08-15.1'),
    'max_input_tokens' => (int) env('OPENAI_MAX_INPUT_TOKENS', 4000),
    'intent_max_output_tokens' => (int) env('OPENAI_INTENT_MAX_OUTPUT_TOKENS', 500),
    'explanation_max_output_tokens' => (int) env('OPENAI_EXPLANATION_MAX_OUTPUT_TOKENS', 900),
    'clarification_threshold_basis_points' => 7000,
    'timeout_seconds' => (int) env('OPENAI_TIMEOUT_SECONDS', 15),
    'connect_timeout_seconds' => (int) env('OPENAI_CONNECT_TIMEOUT_SECONDS', 5),
    'max_retries' => (int) env('OPENAI_MAX_RETRIES', 2),
    'retry_base_delay_ms' => 200,
    'retry_max_delay_ms' => 2000,
    'cache_ttl_seconds' => (int) env('OPENAI_CACHE_TTL_SECONDS', 3600),
    'prices_micro_usd_per_million' => [
        'input' => 200_000,
        'cached_input' => 20_000,
        'output' => 1_250_000,
    ],
    'budgets_micro_usd' => [
        'per_user_daily' => (int) env('OPENAI_USER_DAILY_BUDGET_MICRO_USD', 25_000),
        'per_ip_daily' => (int) env('OPENAI_IP_DAILY_BUDGET_MICRO_USD', 50_000),
        'global_daily' => (int) env('OPENAI_GLOBAL_DAILY_BUDGET_MICRO_USD', 500_000),
        'global_monthly' => (int) env('OPENAI_MONTHLY_CIRCUIT_BREAKER_MICRO_USD', 5_000_000),
    ],
];
