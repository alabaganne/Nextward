<?php

namespace App\Http\Middleware;

use App\Http\Resources\NotificationResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Inertia;
use App\Support\PublicSeo;
use Tighten\Ziggy\Ziggy;
use Closure;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function handle(Request $request, Closure $next)
    {
        Inertia::disableSsr(fn () => !PublicSeo::isPublic($request));
        $response = parent::handle($request, $next);
        if (!PublicSeo::isPublic($request)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        // Public responses contain account actions and CSRF state, never share a user's HTML cache.
        $response->headers->set('Cache-Control', 'private, no-store');
        return $response;
    }

    public function share(Request $request): array
    {
        $user = auth()->user();

        $shared = $user ? [
            'auth' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'image' => $user->image,
					'is_admin' => $user->isAdmin(),
                    'is_candidate' => $user->isCandidate(),
                    'career_stage' => $user->isCandidate() ? $user->userable->career_stage : null,
                    'userable_id' => $user->userable_id,
                    'userable_type' => $user->userable_type,
                    'likes_count' => $user->isCandidate() ? $user->userable->likes()->count() : null,
                    'notifications' => ($user->isCandidate() || $user->isCompany()) ? [
                        'data' => NotificationResource::collection($user->notifications()->take(10)->get()),
                        'unread_count' => $user->unreadNotifications()->count(),
                    ] : null,
                ],
            ],
            'badges' => fn () => [
                'messages' => $user->receivedMessages()->whereNull('read_at')->count(),
            ],

            'toast' => Session::has('toast') ? Session::get('toast') : null,
			'popstate' => false,
        ] : [];

        return array_merge(parent::share($request), [
            'auth' => ['user' => null],
            'toast' => fn () => Session::get('toast'),
            'locale' => $request->is('fr', 'fr/*') ? 'fr' : 'en',
            'ziggy' => fn () => [...(new Ziggy)->toArray(), 'location' => $request->url()],
            'seo' => fn () => PublicSeo::forRequest($request),
        ], $shared);
    }
}
