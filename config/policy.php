<?php

return [
    'global_kill_switch' => (bool) env('POLICY_GLOBAL_KILL_SWITCH', false),
    'admin_token' => env('POLICY_ADMIN_TOKEN'),
];
