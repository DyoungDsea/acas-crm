<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Replaces the original free-text `category` column with a proper
// foreign key to product_categories.
//
// Written to be safe to run against a PARTIALLY-applied state, not
// just a clean one — an earlier run of this migration failed on the
// dropIndex step, but Laravel had already committed the category_id
// column + FK before reaching it (DDL auto-commits per statement in
// MySQL regardless of migration "transactions", and Laravel executes
// add-column operations before drop-index/drop-column ones within
// the same batch). Since the migration never completed, it was never
// marked as run, so `php artisan migrate` will attempt this file
// again from scratch — every step below checks what's actually
// there first instead of assuming nothing happened yet.
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->indexExists('products', 'products_company_id_index')) {
            // The old composite index (company_id, category) was
            // quietly serving as the FK-covering index for company_id
            // (it's the leading column). Dropping it without a
            // replacement fails with "needed in a foreign key
            // constraint" — this is that replacement.
            Schema::table('products', function (Blueprint $table) {
                $table->index('company_id');
            });
        }

        if ($this->indexExists('products', 'products_company_id_category_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['company_id', 'category']);
            });
        }

        if (Schema::hasColumn('products', 'category')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }

        if (! Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignUlid('category_id')->nullable()->after('company_id')
                    ->constrained('product_categories')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }

        if (! Schema::hasColumn('products', 'category')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('category')->after('company_id');
            });
        }

        if (! $this->indexExists('products', 'products_company_id_category_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['company_id', 'category']);
            });
        }

        if ($this->indexExists('products', 'products_company_id_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['company_id']);
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            'SELECT COUNT(1) AS cnt FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return ($result[0]->cnt ?? 0) > 0;
    }
};
