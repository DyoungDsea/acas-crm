<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'contact_id', 'pipeline_stage_id', 'title', 'amount', 'status', 'owner_id',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}
