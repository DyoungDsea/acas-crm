<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class VehicleBooking extends Model
{
    use HasUlids;

    protected $fillable = ['vehicle_id', 'contact_id', 'start_at', 'end_at', 'status'];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
