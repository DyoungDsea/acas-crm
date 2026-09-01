<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;

/**
 * Apply to any model with a company_id column. Registers the global
 * scope AND the company_id relation in one place, so every silo'd
 * model behaves identically instead of each one wiring it up by hand.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (! $model->company_id && auth()->check() && ! auth()->user()->is_super_admin) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
