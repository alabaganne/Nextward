<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RememberSignupRef
{
    public function handle(Request $request, Closure $next)
    {
        $ref = $request->query('ref');

        if ($request->isMethod('GET') && ! $request->user() && is_string($ref) && preg_match('/^[A-Za-z0-9-]{1,60}$/', $ref)
            && ! $request->session()->has('signup_ref')) {
            $request->session()->put('signup_ref', strtolower($ref));
        }

        return $next($request);
    }
}
