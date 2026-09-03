<?php

return [
    'poewiki_import_enabled' => (bool) env('POEWIKI_IMPORT_ENABLED', false),
    'poeninja_economy_enabled' => (bool) env('POENINJA_ECONOMY_ENABLED', false),
    'openai_explanations_enabled' => (bool) env('OPENAI_EXPLANATIONS_ENABLED', false),
    'openai_intent_enabled' => (bool) env('OPENAI_INTENT_ENABLED', false),
    'ggg_passive_tree' => [
        'enabled' => (bool) env('GGG_PASSIVE_TREE_IMPORT_ENABLED', false),
        'contact' => env('GGG_PASSIVE_TREE_CONTACT'),
        'user_agent_version' => env('GGG_PASSIVE_TREE_USER_AGENT_VERSION', '0.1.0'),
        'connect_timeout_seconds' => max(1, (int) env('GGG_PASSIVE_TREE_CONNECT_TIMEOUT_SECONDS', 3)),
        'request_timeout_seconds' => max(1, (int) env('GGG_PASSIVE_TREE_REQUEST_TIMEOUT_SECONDS', 15)),
        'schema_version' => '1.0.0',
        'ruleset_parser_version' => '1.0.0',
        'approved_revisions' => [
            '8bd138b32ea2631455cac5935bfab089f826094f' => [
                'patch' => '3.29.1',
                'source_checksum_sha256' => '7e9f755e33152129ebf36c2ebdad639c527e4ad70d274b1fefb860f30ca01122',
            ],
        ],
    ],
    'poe2_dataset' => [
        'enabled' => (bool) env('POE2_DATASET_IMPORT_ENABLED', false),
        'approved_revisions' => ['poe2-0.3.0' => [
            'patch' => '0.3.0',
            'source_checksum_sha256' => '21c382a99ab3fd634546efb32951468e4343404a06e061c86e1925873f2ac8f3',
        ]],
    ],
];
