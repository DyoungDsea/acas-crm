<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Receipt extends Model
{
    use HasUlids;

    protected $fillable = [
        'payment_id',
        'receipt_number',
        'pdf_path',
        'issued_by',
        'issued_at',
        'voided_at',
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

    /**
     * Generates ACAS-PHM-2026-000118-style receipt numbers, meant to
     * be sequential PER COMPANY. Centralized here rather than
     * duplicated — the original API-only version was a genuine bug:
     * Receipt has no company_id column of its own (it only has one
     * indirectly, via payment -> order -> company), and the old code
     * did `Receipt::whereYear(...)->count()` with NO scoping at
     * all, meaning every company on the entire platform was sharing
     * one single global sequence instead of each having their own.
     */
    public static function generateNumberFor(Company $company): string
    {
        $prefix = strtoupper(Str::substr(Str::slug($company->name), 0, 3));
        $year = now()->year;
        $sequence = str_pad(
            (string) (static::whereHas('payment.order', fn($q) => $q->where('company_id', $company->id))
                ->whereYear('created_at', $year)->count() + 1),
            6,
            '0',
            STR_PAD_LEFT
        );

        return "{$prefix}-{$year}-{$sequence}";
    }
}
