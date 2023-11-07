<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class Suspended
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {

            if (Auth::user()->getGroupId() == config('app.suspended_gid')) {

                if (strpos('suspended,auth.signout,support,support.contact_form', Route::currentRouteName()) === false) {
                    return redirect()->route('suspended');
                }
            }
        }

        return $next($request);
    }
}
