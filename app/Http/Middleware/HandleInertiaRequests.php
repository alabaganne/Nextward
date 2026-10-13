<?php

namespace App\Http\Middleware;

use App\Http\Resources\NotificationResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Inertia\Middleware;

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
                    'userable_id' => $user->userable_id,
                    'userable_type' => $user->userable_type,
                    'likes_count' => $user->isStudent() ? $user->userable->likes->count() : null,
                    'notifications' => ($user->isStudent() || $user->isCompany()) ? [
                        'data' => NotificationResource::collection($user->notifications()->take(10)->get()),
                        'unread_count' => $user->unreadNotifications->count(),
                    ] : null,
                ],
            ],

            'toast' => Session::has('toast') ? Session::get('toast') : null,
			'popstate' => false,
        ] : [];

        return array_merge(parent::share($request), $shared);
    }
}
