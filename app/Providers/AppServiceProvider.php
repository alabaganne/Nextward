<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;
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
        \App\Models\Internship::observe(\App\Observers\OpportunityTranslationObserver::class);

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

        VerifyEmail::toMailUsing(fn ($user, string $url) => (new MailMessage)
            ->subject('Confirm your email for Nextward')
            ->greeting('Hi '.Str::of($user->name)->before(' ').',')
            ->line('Thanks for joining Nextward. Please confirm this is your email address so you can apply to roles, message employers and get alerts.')
            ->action('Confirm my email', $url)
            ->line('The link works for '.config('auth.verification.expire', 60).' minutes. If you did not create an account, you can ignore this email.'));

        ResetPassword::toMailUsing(fn ($user, string $token) => (new MailMessage)
            ->subject('Reset your Nextward password')
            ->greeting('Hi '.Str::of($user->name)->before(' ').',')
            ->line('We got a request to reset the password for your Nextward account.')
            ->action('Choose a new password', url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)))
            ->line('The link works for '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes. If you did not ask for this, you can ignore this email and your password stays the same.'));
    }
}
