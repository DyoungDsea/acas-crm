<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Records which pharmacy batch(es) an order item's quantity was drawn
// from, and how much came from each. A single item can span more
// than one batch (FEFO drawdown moves to the next batch once the
// soonest-expiring one runs out), so this is a one-to-many link, not
// a single foreign key on order_items.
//
// This table exists purely so an order edit can reverse a drawdown
// accurately — without it, editing an order's items would have no
// way to know how much stock to give back, or to which batch.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // order_items uses a plain auto-increment id (see that
            // migration's own comment — it's never opened via its own
            // URL), so this FK is foreignId, not foreignUlid.
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('pharmacy_batch_id')->constrained('pharmacy_batches')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_batches');
    }
};
