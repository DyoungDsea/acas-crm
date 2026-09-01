<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // UDFDB Agric Services (agric investment, agro management,
        // consultancy) — and reusable as-is for ACAS Farms' own
        // "consultant/advisory" line of business if that's tracked
        // separately from crop_cycles/livestock_batches.
        Schema::create('engagements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type'); // investment, management, consultancy
            $table->text('scope')->nullable();
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->foreignUlid('lead_advisor')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagements');
    }
};
