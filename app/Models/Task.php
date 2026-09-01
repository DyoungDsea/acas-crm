<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'taskable_type', 'taskable_id', 'title', 'description', 'due_at', 'status', 'assigned_to',
    ];

    protected $casts = ['due_at' => 'datetime'];

    public function taskable()
    {
        return $this->morphTo();
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
