<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'subject_type', 'subject_id', 'type', 'description', 'user_id', 'occurred_at',
    ];

    protected $casts = ['occurred_at' => 'datetime'];

    public function subject()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
