<?php

return [
    'require_verified_email' => (bool) env('AUTH_REQUIRE_VERIFIED_EMAIL', false),

    'emergency' => [
        'imports' => (bool) env('IMPORTS_ENABLED', true),
        'rulesets' => (bool) env('RULESETS_ENABLED', true),
        'external_links' => (bool) env('EXTERNAL_LINKS_ENABLED', true),
        'ai' => (bool) env('OPENAI_ENABLED', false),
        // Funding cannot be enabled by configuration. Policy and code review
        // are required before this constant may change.
        'funding' => false,
    ],

    'outbound' => [
        'enabled' => (bool) env('OUTBOUND_NETWORK_ENABLED', false),
        'targets' => [
            'openai.responses' => [
                'scheme' => 'https',
                'host' => 'api.openai.com',
                'port' => 443,
                'path' => '/v1/responses',
            ],
        ],
    ],

    'policy_admin' => [
        'enabled' => (bool) env('POLICY_ADMIN_ENABLED', false),
        'minimum_token_length' => 32,
        'evidence_hosts' => [
            'developers.openai.com',
            'github.com',
            'pobb.in',
            'www.pathofexile.com',
        ],
    ],

    'retention' => [
        'analysis_days' => max(1, (int) env('ANALYSIS_RETENTION_DAYS', 30)),
        'ai_audit_days' => max(1, (int) env('AI_AUDIT_RETENTION_DAYS', 30)),
        'deleted_session_tombstone_days' => max(1, (int) env('DELETED_SESSION_TOMBSTONE_DAYS', 7)),
    ],
];
