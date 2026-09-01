<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
    use HasUlids;

    protected $fillable = ['pipeline_id', 'name', 'order'];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }
}
