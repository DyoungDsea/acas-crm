<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ACAS Farms. Covers poultry and fishery — one row per reared
        // batch (a flock, a pond stocking).
        Schema::create('livestock_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // poultry, fishery
            $table->string('batch_label');
            $table->unsignedInteger('quantity_start');
            $table->unsignedInteger('quantity_current');
            $table->date('start_date');
            $table->date('expected_ready_at')->nullable();
            $table->string('status')->default('active'); // active, sold, closed
            $table->timestamps();

            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestock_batches');
    }
};
