<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = ['company_id', 'name'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
