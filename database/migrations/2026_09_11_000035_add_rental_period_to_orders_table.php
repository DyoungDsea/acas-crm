<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Generic on purpose, not vehicle_rental_start_at — an Order is the
// one ledger every sale and rental in this app goes through (see
// VehicleTransactionPageController), so "this order covers a period,
// not just a point in time" is a property of Orders in general. Right
// now only vehicle rentals populate these two columns, but a future
// short-let property booking could reuse them without another
// migration.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('rental_start_at')->nullable()->after('total_amount');
            $table->date('rental_end_at')->nullable()->after('rental_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['rental_start_at', 'rental_end_at']);
        });
    }
};
