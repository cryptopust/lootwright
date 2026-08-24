<?php

return [
    // These are non-secret operator evidence identifiers. A release gate may
    // pass only after the corresponding reviewed CI/staging record exists.
    'security_acceptance_id' => env('RELEASE_SECURITY_ACCEPTANCE_ID'),
    'staging_acceptance' => [
        'poe1' => env('RELEASE_POE1_STAGING_ACCEPTANCE_ID'),
        'poe2' => env('RELEASE_POE2_STAGING_ACCEPTANCE_ID'),
    ],
];
