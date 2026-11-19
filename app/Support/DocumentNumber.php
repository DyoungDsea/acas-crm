<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// One place that builds ORDER / RECEIPT numbers.
//
// Why it exists: the number prefix is the first 3 letters of the
// company name, so every company starting "ACA" (ACAS Pharmacy, ACAS
// Hotel, ...) gets the same prefix. The old per-company COUNT()+1
// then produced the same number in two companies, and because
// orders.order_number / receipts.receipt_number are UNIQUE across the
// whole table, the second insert threw. Here the next number is read
// from the highest number already issued with that prefix+year, across
// all companies, so the counter is shared and can never collide.
class DocumentNumber
{
    public static function order(string $companyName): string
    {
        return self::next('orders', 'order_number', $companyName);
    }

    public static function receipt(string $companyName): string
    {
        return self::next('receipts', 'receipt_number', $companyName);
    }

    private static function next(string $table, string $column, string $companyName): string
    {
        $prefix = strtoupper(Str::substr(Str::slug($companyName), 0, 3));
        $year = now()->year;
        $stem = "{$prefix}-{$year}-";

        $max = DB::table($table)->where($column, 'like', $stem . '%')->max($column);
        $last = $max ? (int) substr($max, -6) : 0;

        return $stem . str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }
}
