<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;

class ResolveCompanyTenant
{
    /**
     * For routes shaped /companies/{company:slug}/... — binds the
     * company and verifies the acting user is allowed to operate in
     * it. Super admins may pass through for any company; everyone
     * else must belong to the company in the URL. This is the second
     * enforcement layer alongside the CompanyScope global scope —
     * URL-level tampering (typing another company's slug) is caught
     * here before a query ever runs.
     */
    public function handle(Request $request, Closure $next)
    {
        $company = $request->route('company');

        if (! $company instanceof Company) {
            abort(404);
        }

        $user = $request->user();

        if (! $user->is_super_admin && $user->company_id !== $company->id) {
            abort(403, 'You do not have access to this company.');
        }

        return $next($request);
    }
}
