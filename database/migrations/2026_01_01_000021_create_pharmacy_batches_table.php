<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ACAS Pharmacy. Each stock intake of a product is its own batch,
        // since expiry dates differ intake to intake even for the same
        // drug. Sales draw down whichever batch expires soonest (FEFO).
        Schema::create('pharmacy_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number');
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_remaining');
            $table->date('expiry_date');
            $table->date('received_at');
            $table->string('status')->default('active'); // active, expired, depleted
            $table->timestamps();

            $table->index(['company_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_batches');
    }
};
