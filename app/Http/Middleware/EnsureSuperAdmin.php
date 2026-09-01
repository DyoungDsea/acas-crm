<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user() || ! $request->user()->is_super_admin) {
            abort(403, 'This action is restricted to super admins.');
        }

        return $next($request);
    }
}
