<?php

return [
    'default_retention_hours' => (int) env('POB_IMPORT_RETENTION_HOURS', 24),
    'maximum_retention_hours' => (int) env('POB_IMPORT_MAX_RETENTION_HOURS', 168),
];
