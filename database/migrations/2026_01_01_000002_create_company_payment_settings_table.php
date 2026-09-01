<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per company. Not routed to individually, so a normal
        // auto-increment id is fine here — this table is never linked
        // to directly, only read/updated via its parent company.
        Schema::create('company_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('allow_partial_payments')->default(true);
            $table->boolean('require_full_payment')->default(false);
            $table->unsignedTinyInteger('minimum_partial_percent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_payment_settings');
    }
};
