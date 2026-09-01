<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained()->cascadeOnDelete();
            // Human-readable business number (e.g. ACAS-PHM-2026-000241).
            // This is for display/printing only — NEVER used for URL
            // navigation. The ulid `id` above is what routes use.
            $table->string('order_number')->unique();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status')->default('unpaid'); // unpaid, partially_paid, paid, void
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
