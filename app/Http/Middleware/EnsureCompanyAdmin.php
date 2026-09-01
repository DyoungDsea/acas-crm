<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompanyAdmin
{
    /**
     * Allows company admins (their own company) and super admins
     * (any company). Staff without the company_admin role are blocked.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || (! $user->is_super_admin && ! $user->hasRole('company_admin'))) {
            abort(403, 'This action is restricted to company admins.');
        }

        return $next($request);
    }
}
