<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Enforces strict silo access: a logged-in user only ever sees
     * records belonging to their own company. Super admins bypass
     * this entirely. Unauthenticated contexts (e.g. mid-login lookup,
     * console commands, queued jobs run as "the system") are left
     * unscoped on purpose — apply scoping explicitly in those cases
     * via ->forCompany($id) if needed.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->is_super_admin) {
            return;
        }

        $builder->where($model->getTable().'.company_id', $user->company_id);
    }
}
