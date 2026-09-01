<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('property_id')->constrained()->cascadeOnDelete();
            $table->string('unit_label'); // e.g. "Flat 3B"
            $table->decimal('rent_amount', 14, 2)->nullable();
            $table->string('status')->default('vacant'); // vacant, occupied
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_units');
    }
};
