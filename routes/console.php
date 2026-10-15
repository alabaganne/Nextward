<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Source next_run_at controls the daily cadence, retries and bounded backlogs.
Schedule::command('opportunities:import --due --limit=100')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('opportunities:reconcile')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('opportunities:digests')->hourly()->withoutOverlapping();
Schedule::command('opportunities:indexing --limit=100')->hourly()->withoutOverlapping();
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put('opportunities:scheduler:last_seen_at', now()->toIso8601String(), now()->addDay()))
    ->everyMinute()->name('opportunities.scheduler-heartbeat');
