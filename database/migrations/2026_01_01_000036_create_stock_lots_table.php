<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_lots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('vendor_contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('reference')->nullable(); // supplier invoice / delivery ref, free text
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_remaining');
            $table->date('received_at');
            $table->string('status')->default('active'); // active, depleted
            $table->timestamps();

            $table->index(['company_id', 'product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
    }
};
