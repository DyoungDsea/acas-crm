<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SolarProject extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'contact_id', 'title', 'type', 'status', 'start_date', 'completion_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function siteSurveys()
    {
        return $this->hasMany(SiteSurvey::class);
    }
}
