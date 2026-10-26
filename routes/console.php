<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Source next_run_at controls the daily cadence, retries and bounded backlogs.
Schedule::command('opportunities:import --due --limit='.config('importer.run_limit'))->everyFiveMinutes()->withoutOverlapping();
Schedule::command('opportunities:reconcile')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('opportunities:digests')->hourly()->withoutOverlapping();
Schedule::command('opportunities:indexing --limit=100')->hourly()->withoutOverlapping();
Schedule::command('companies:logos')->hourly()->withoutOverlapping();
Schedule::command('companies:research')->hourly()->withoutOverlapping();
Schedule::command('leads:daily')->dailyAt('07:00')->timezone('Africa/Tunis')->withoutOverlapping();
Schedule::command('opportunities:discover')->weeklyOn(1, '06:00')->timezone('Africa/Tunis')->withoutOverlapping();
Schedule::command('seo:indexnow')->hourly()->withoutOverlapping();
Schedule::command('opportunities:translate --limit=200')->everyTenMinutes()->withoutOverlapping();
Schedule::command('credits:monthly')->monthlyOn(1, '06:00')->timezone('Africa/Tunis')->withoutOverlapping();
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put('opportunities:scheduler:last_seen_at', now()->toIso8601String(), now()->addDay()))
    ->everyMinute()->name('opportunities.scheduler-heartbeat');
