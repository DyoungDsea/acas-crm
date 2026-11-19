<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-company receipt branding. Header/footer are image
        // banners (the same artwork the company already uses on its
        // Word letterhead); the note is plain text printed above the
        // footer, e.g. "Goods sold are not returnable".
        Schema::table('companies', function (Blueprint $table) {
            $table->string('receipt_header_path')->nullable();
            $table->string('receipt_footer_path')->nullable();
            $table->text('receipt_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['receipt_header_path', 'receipt_footer_path', 'receipt_note']);
        });
    }
};
