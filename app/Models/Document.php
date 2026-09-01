<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'documentable_type', 'documentable_id', 'file_path', 'original_name', 'uploaded_by',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
