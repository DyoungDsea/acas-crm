<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Super admins bypass every permission/role check automatically —
        // this is what makes `is_super_admin` a true full-control flag
        // rather than something that has to be re-checked in every
        // controller and Blade @can directive. Combined with the
        // EnsureSuperAdmin middleware (route-level) and the CompanyScope
        // global scope bypass (query-level), this is the third and
        // final layer: authorization checks (Gates/Policies/@can).
        Gate::before(function ($user, string $ability) {
            return $user->is_super_admin ? true : null;
        });
    }
}
