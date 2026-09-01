<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && ! $request->user()->is_active) {
            abort(403, 'Your account has been deactivated. Contact your company admin.');
        }

        return $next($request);
    }
}
