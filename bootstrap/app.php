<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\RememberSignupRef::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->trustProxies(at: '*');

        // Payment gateways call these from their own servers.
        $middleware->validateCsrfTokens(except: ['webhooks/billing/*']);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if ($status === 419 && ! $request->expectsJson()) {
                return back()->with('toast', ['type' => 'error', 'message' => __('Your session expired. Please try again.', [], $request->is('fr', 'fr/*') ? 'fr' : 'en')]);
            }

            if (! config('app.debug') && in_array($status, [403, 404, 500, 503], true) && ! $request->expectsJson()) {
                try {
                    return Inertia::render('Error', ['status' => $status])->toResponse($request)->setStatusCode($status);
                } catch (Throwable) {
                    return $response; // e.g. the database is down, keep Laravel's plain page
                }
            }

            return $response;
        });
    })->create();
