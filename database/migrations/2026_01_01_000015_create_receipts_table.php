<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One receipt per payment (not per order) — a 3-installment
        // lease payment produces 3 receipts. voided_at marks a receipt
        // as void without ever deleting or editing it (audit integrity).
        Schema::create('receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('receipt_number')->unique(); // e.g. ACAS-HOM-2026-000118
            $table->string('pdf_path')->nullable();
            $table->foreignUlid('issued_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('issued_at');
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
