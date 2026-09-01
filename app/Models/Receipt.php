<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasUlids;

    protected $fillable = [
        'payment_id', 'receipt_number', 'pdf_path', 'issued_by', 'issued_at', 'voided_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function issuedByUser()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /**
     * Void, never delete or edit — preserves the audit trail. Callers
     * should issue a fresh, correct receipt separately.
     */
    public function void(): void
    {
        $this->update(['voided_at' => now()]);
    }
}
