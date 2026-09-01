<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ACAS Farms. Covers rice production, agro farm, and cash crop —
        // one row per planting cycle on a given plot.
        Schema::create('crop_cycles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('crop_type'); // rice, maize, cash_crop, etc.
            $table->string('plot_label');
            $table->date('planted_at');
            $table->date('expected_harvest_at')->nullable();
            $table->date('harvested_at')->nullable();
            $table->decimal('yield_quantity', 12, 2)->nullable();
            $table->string('yield_unit')->nullable(); // bags, tons, kg
            $table->string('status')->default('planted'); // planted, growing, harvested, closed
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_cycles');
    }
};
