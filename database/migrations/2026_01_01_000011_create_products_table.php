<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One generic catalog table for everything sellable: a sack of
        // rice, a pharmacy drug, a solar panel, a car part. The `category`
        // column separates them, and `attributes` (JSON) holds whatever
        // extra fields a category needs without new columns/migrations.
        Schema::create('products', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // rice, drug, vehicle_part, solar_kit, etc.
            $table->string('name');
            $table->string('sku')->nullable();
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->json('attributes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
