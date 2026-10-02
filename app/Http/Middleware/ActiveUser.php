<?php

namespace App\Http\Middleware;

class ActiveUser
{
    public function handle($request, \Closure $next)
    {
        abort_unless($request->user()?->active, 403);

        return $next($request);
    }
}
