<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'logo_path',
        'address',
        'phone',
        'email',
        'is_active',
        'receipt_header_path',
        'receipt_footer_path',
        'receipt_note'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public const PIPELINE_ENABLED_TYPES = [
        'farms',
        'properties',
        'trading',
        'energy',
        'automotive',
        'agric_services',
    ];
    /**
     * Company types that track sellable stock quantities (StockLot). Orders
     * for these draw stock down and refuse to go through when there isn't
     * enough.
     */
    public const STOCK_TRACKED_TYPES = ['hair_vendor'];

    public function tracksStock(): bool
    {
        return $this->type === 'pharmacy' || in_array($this->type, self::STOCK_TRACKED_TYPES, true);
    }

    public function paymentSetting()
    {
        return $this->hasOne(CompanyPaymentSetting::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function pipelines()
    {
        return $this->hasMany(Pipeline::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function pharmacyBatches()
    {
        return $this->hasMany(PharmacyBatch::class);
    }

    public function cropCycles()
    {
        return $this->hasMany(CropCycle::class);
    }

    public function livestockBatches()
    {
        return $this->hasMany(LivestockBatch::class);
    }

    public function solarProjects()
    {
        return $this->hasMany(SolarProject::class);
    }

    public function engagements()
    {
        return $this->hasMany(Engagement::class);
    }

    /**
     * The key used to resolve this company's Bootstrap theme —
     * public/css/themes/{themeKey}.css. Reuses the existing `type`
     * column (farms, properties, pharmacy, trading, energy,
     * automotive, agric_services) rather than adding a redundant
     * column, since each of the 7 companies already has a unique
     * type. Falls back to slug for any future company added without
     * a type set.
     */
    public function themeKey(): string
    {
        return $this->type ?: $this->slug;
    }
}
