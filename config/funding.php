<?php

return [
    // This is an operator request, not an authorization. The application
    // provider also requires reviewed metadata and an executable Policy Gate
    // allow decision. No payment adapter exists in this release.
    'requested_enabled' => (bool) env('FUNDING_ENABLED', false),

    'activation' => [
        'policy_decision_id' => env('FUNDING_POLICY_DECISION_ID'),
        'policy_decision_date' => env('FUNDING_POLICY_DECISION_DATE'),
        'evidence_record_id' => env('FUNDING_EVIDENCE_RECORD_ID'),
        'operator_acknowledged' => (bool) env('FUNDING_OPERATOR_ACKNOWLEDGED', false),
        'disclosure_version' => env('FUNDING_DISCLOSURE_VERSION'),
    ],

    // Public projections only. These settings contain no player, build,
    // account, donor, or request data and are not written to analysis tables.
    'costs' => [
        'currency' => 'USD',
        'pricing_model' => env('OPENAI_INTENT_MODEL', 'gpt-5.4-nano'),
        'pricing_reviewed_on' => '2026-08-15',
        'pricing_source' => 'https://developers.openai.com/api/docs/pricing',
        'hosting_monthly_cents' => [
            'compute_database_cache' => (int) env('FUNDING_COST_COMPUTE_MONTHLY_CENTS', 3_500),
            'backups' => (int) env('FUNDING_COST_BACKUPS_MONTHLY_CENTS', 1_000),
            'observability_and_domain' => (int) env('FUNDING_COST_OPERATIONS_MONTHLY_CENTS', 1_000),
        ],
        'scenarios' => [
            'low' => [
                'analyses_per_month' => (int) env('FUNDING_LOW_ANALYSES_PER_MONTH', 1_000),
                'ai_usage_rate_basis_points' => 2_500,
                'ai_calls_per_enabled_analysis' => 1,
                'uncached_input_tokens_per_call' => 700,
                'cached_input_tokens_per_call' => 200,
                'output_tokens_per_call' => 180,
            ],
            'base' => [
                'analyses_per_month' => (int) env('FUNDING_BASE_ANALYSES_PER_MONTH', 10_000),
                'ai_usage_rate_basis_points' => 4_000,
                'ai_calls_per_enabled_analysis' => 1,
                'uncached_input_tokens_per_call' => 900,
                'cached_input_tokens_per_call' => 300,
                'output_tokens_per_call' => 240,
            ],
            'high' => [
                'analyses_per_month' => (int) env('FUNDING_HIGH_ANALYSES_PER_MONTH', 50_000),
                'ai_usage_rate_basis_points' => 6_000,
                'ai_calls_per_enabled_analysis' => 2,
                'uncached_input_tokens_per_call' => 1_100,
                'cached_input_tokens_per_call' => 400,
                'output_tokens_per_call' => 320,
            ],
        ],
    ],
];
