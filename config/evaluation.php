<?php

return [
    'schema_version' => '1.0.0',
    'suite_version' => '2026-08-16.1',
    'reports_directory' => storage_path('app/evaluations'),
    'private_fixtures_directory' => base_path('evals/private'),
    'thresholds' => [
        'parser_success_rate_basis_points' => 10_000,
        'parser_safe_failure_rate_basis_points' => 10_000,
        'edition_detection_precision_basis_points' => 10_000,
        'deterministic_finding_precision_basis_points' => 10_000,
        'forbidden_cross_edition_recommendations_max' => 0,
        'unsupported_disclosure_rate_basis_points' => 10_000,
        'recommendation_trace_completeness_basis_points' => 10_000,
        'trade_trace_completeness_basis_points' => 10_000,
        'undocumented_endpoint_or_network_calls_max' => 0,
        'ai_schema_validity_basis_points' => 10_000,
        'ai_canonical_id_resolution_basis_points' => 10_000,
        'hallucinated_canonical_ids_accepted_max' => 0,
        'deterministic_replay_equality_basis_points' => 10_000,
        'case_latency_max_ms' => 500,
        'case_memory_delta_max_bytes' => 16_777_216,
        'estimated_input_tokens_per_call_max' => (int) env('OPENAI_MAX_INPUT_TOKENS', 4_000),
        'estimated_output_tokens_per_call_max' => (int) env('OPENAI_EXPLANATION_MAX_OUTPUT_TOKENS', 900),
        'estimated_cost_per_call_micro_usd_max' => 2_000,
    ],
    'extended' => [
        'replay_iterations' => 50,
    ],
    'live' => [
        'enabled' => (bool) env('OPENAI_LIVE_EVALS_ENABLED', false),
        'ci_detected' => (bool) env('CI', false),
        'maximum_cases' => 3,
    ],
];
