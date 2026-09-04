<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Optional link from a stock intake batch to the vendor (a Contact,
// normally of type 'vendor') who supplied it — lets a pharmacy
// answer "who did we buy this batch from?" without forcing every
// batch to have one, since not every intake needs to be traced back
// to a specific supplier.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_batches', function (Blueprint $table) {
            $table->foreignUlid('vendor_contact_id')->nullable()->after('product_id')
                ->constrained('contacts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_contact_id');
        });
    }
};
