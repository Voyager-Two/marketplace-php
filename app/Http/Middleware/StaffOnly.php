<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class StaffOnly
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
        $group_id = 0;

        if (Auth::guard($guard)->check()) {
            $group_id = Auth::user()->getGroupId();
        }

        if (!isStaff($group_id)) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
