<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use BelongsToCompany, HasUlids, SoftDeletes;

    protected $fillable = [
        'company_id', 'type', 'name', 'email', 'phone', 'address', 'notes', 'assigned_to',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function leases()
    {
        return $this->hasMany(Lease::class);
    }

    public function vehicleBookings()
    {
        return $this->hasMany(VehicleBooking::class);
    }

    public function solarProjects()
    {
        return $this->hasMany(SolarProject::class);
    }

    public function engagements()
    {
        return $this->hasMany(Engagement::class);
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
