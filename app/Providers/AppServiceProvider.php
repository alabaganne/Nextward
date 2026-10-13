<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        JsonResource::withoutWrapping();

        Relation::morphMap([
            'student' => \App\Models\Student::class,
            'company' => \App\Models\Company::class,
            'internship' => \App\Models\Internship::class,
        ]);

        \App\Models\Internship::observe(\App\Observers\OpportunityIndexingObserver::class);

        \Illuminate\Support\Facades\Event::listen(\Inertia\Ssr\SsrRenderFailed::class, function ($event) {
            \Illuminate\Support\Facades\Log::error('Public server rendering failed.', [
                'component' => $event->component(),
                'path' => parse_url($event->url(), PHP_URL_PATH),
                'type' => $event->type->value,
            ]);
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
