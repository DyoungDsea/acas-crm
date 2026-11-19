<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Same job as order_item_batches does for pharmacy: records which
// stock lot(s) an order item's quantity was drawn from, and how much
// came from each, so editing an order can give stock back to the
// right lots. A single item can span more than one lot when the
// oldest one runs out partway through.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_stock_lots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // order_items uses a plain auto-increment id, so this FK
            // is foreignId, not foreignUlid.
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('stock_lot_id')->constrained('stock_lots')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_stock_lots');
    }
};
