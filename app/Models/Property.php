<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = ['company_id', 'title', 'type', 'address', 'status'];

    public function units()
    {
        return $this->hasMany(PropertyUnit::class);
    }
}
