<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = ['company_id', 'name', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function stages()
    {
        return $this->hasMany(PipelineStage::class)->orderBy('order');
    }
}
