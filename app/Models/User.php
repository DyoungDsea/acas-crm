<?php

namespace App\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // BelongsToCompany deliberately NOT used here — that trait
    // attaches CompanyScope, which calls auth()->check() on every
    // query. Since Laravel's own auth resolution queries this exact
    // model (EloquentUserProvider::retrieveById(), run on every
    // authenticated request), that creates infinite recursion:
    // resolving the logged-in user requires querying User, which
    // requires knowing the logged-in user to decide scoping, which
    // requires resolving the logged-in user... forever, until the
    // stack overflows. Company-scoping for user listings is instead
    // done explicitly where it's actually needed (see
    // Api\UserController::index()), which doesn't have this problem
    // because it isn't in the auth-resolution path.
    use HasRoles, HasUlids, Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'is_super_admin',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_super_admin' => 'boolean',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedContacts()
    {
        return $this->hasMany(Contact::class, 'assigned_to');
    }

    public function ownedDeals()
    {
        return $this->hasMany(Deal::class, 'owner_id');
    }

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Where this user lands after login. Super admins go to the
     * cross-company dashboard; everyone else goes to their own
     * company's dashboard. Called from AuthenticatedSessionController
     * so the redirect rule exists in exactly one place.
     */
    public function dashboardUrl(): string
    {
        if ($this->is_super_admin) {
            return route('super-admin.dashboard');
        }

        return route('company.dashboard', ['company' => $this->company->slug]);
    }

    /**
     * Up to two initials for the header avatar circle, e.g.
     * "Pharmacy Admin" -> "PA".
     */
    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }
}
