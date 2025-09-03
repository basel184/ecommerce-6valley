<?php

namespace App\Http\Middleware;

use Closure;

class InstallationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Skip license validation - allow installation to proceed
        return $next($request);
    }
}
