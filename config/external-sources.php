<?php

use Lootwright\Application\ExternalSources\DTO\EconomyCategory;

return [
    'poe_ninja' => [
        'enabled' => (bool) env('POE_NINJA_ENABLED', false)
            && (bool) env('POENINJA_ECONOMY_ENABLED', false),
        'base_url' => env('POE_NINJA_BASE_URL', 'https://poe.ninja'),
        'contact' => env('POE_NINJA_CONTACT'),
        'user_agent_version' => env('POE_NINJA_USER_AGENT_VERSION', '0.1.0'),
        'refresh_seconds' => max(300, (int) env('POE_NINJA_REFRESH_SECONDS', 1200)),
        'stale_seconds' => max(300, (int) env('POE_NINJA_STALE_SECONDS', 21600)),
        'connect_timeout_seconds' => max(1, (int) env('POE_NINJA_CONNECT_TIMEOUT_SECONDS', 3)),
        'request_timeout_seconds' => max(1, (int) env('POE_NINJA_REQUEST_TIMEOUT_SECONDS', 10)),
        'exchange_categories' => EconomyCategory::exchangeValues(),
        'stash_categories' => EconomyCategory::stashValues(),
    ],
    'poe_wiki_cargo' => [
        'enabled' => (bool) env('POE_WIKI_CARGO_ENABLED', false)
            && (bool) env('POEWIKI_IMPORT_ENABLED', false),
    ],
    'ggg_oauth' => ['enabled' => (bool) env('GGG_OAUTH_ENABLED', false)],
];
