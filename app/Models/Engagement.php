<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Engagement extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'contact_id', 'title', 'type', 'scope', 'status',
        'start_date', 'end_date', 'lead_advisor',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function leadAdvisor()
    {
        return $this->belongsTo(User::class, 'lead_advisor');
    }
}
