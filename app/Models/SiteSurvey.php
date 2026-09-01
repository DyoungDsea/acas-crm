<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SiteSurvey extends Model
{
    use HasUlids;

    protected $fillable = ['solar_project_id', 'surveyed_by', 'survey_date', 'findings', 'status'];

    protected $casts = ['survey_date' => 'date'];

    public function solarProject()
    {
        return $this->belongsTo(SolarProject::class);
    }

    public function surveyor()
    {
        return $this->belongsTo(User::class, 'surveyed_by');
    }
}
