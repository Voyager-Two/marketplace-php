<?php

namespace App\Http\Middleware;

use Closure;

class CloudFlare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // if CloudFlare request, set correct proper client ip address
        $HTTP_CF_CONNECTING_IP = $request->server->get('HTTP_CF_CONNECTING_IP');

        if ($HTTP_CF_CONNECTING_IP) {
            $request->server->set('REMOTE_ADDR', $HTTP_CF_CONNECTING_IP);
        }

        return $next($request);
    }
}
