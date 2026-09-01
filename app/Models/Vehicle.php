<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = ['company_id', 'make', 'model', 'registration_number', 'status'];

    public function bookings()
    {
        return $this->hasMany(VehicleBooking::class);
    }
}
