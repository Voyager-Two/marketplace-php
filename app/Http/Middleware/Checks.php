<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class Checks
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
        // change app/game if requested
        // store it in the session
        processGameChange($request->get('app_id'));

        // welcome check
        if (Auth::guard($guard)->check()) {

            if (Auth::user()->getEmail() == null) {

                if (strpos('welcome,welcome.post,about,terms,privacy,support,auth.signout', Route::currentRouteName()) === false) {
                    return redirect()->route('welcome');
                }
            }
        }

        return $next($request);
    }
}
