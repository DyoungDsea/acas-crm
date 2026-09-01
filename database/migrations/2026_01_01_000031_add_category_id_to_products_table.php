<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Replaces the original free-text `category` column with a proper
// foreign key to product_categories — a controlled, per-company list
// instead of whatever string a staff member happened to type.
// nullOnDelete() matters here: deleting a category (see
// ProductCategoryPageController::destroy()) should leave its
// products simply uncategorized, not delete the products themselves.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'category']);
            $table->dropColumn('category');
            $table->foreignUlid('category_id')->nullable()->after('company_id')
                ->constrained('product_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->string('category')->after('company_id');
            $table->index(['company_id', 'category']);
        });
    }
};
