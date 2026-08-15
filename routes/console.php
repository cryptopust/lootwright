<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pob:prune-imports')->hourly()->withoutOverlapping();
Schedule::command('analysis:prune-artifacts')->hourly()->withoutOverlapping();
Schedule::command('security:prune-retained-data')->dailyAt('03:15')->withoutOverlapping();
Schedule::command('workflow:dispatch-outbox')->everyMinute()->withoutOverlapping();
