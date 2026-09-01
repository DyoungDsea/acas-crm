<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use BelongsToCompany, HasUlids;

    protected $fillable = [
        'company_id', 'contact_id', 'order_number', 'total_amount', 'status', 'created_by',
    ];

    protected $casts = ['total_amount' => 'decimal:2'];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balanceDue(): float
    {
        return (float) $this->total_amount - $this->amountPaid();
    }

    /**
     * Recompute status from actual payments — call after any payment
     * is created/voided so status never drifts from reality.
     */
    public function refreshStatus(): void
    {
        $paid = $this->amountPaid();

        $this->status = match (true) {
            $paid <= 0 => 'unpaid',
            $paid < $this->total_amount => 'partially_paid',
            default => 'paid',
        };

        $this->save();
    }
}
