<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Spatie's published permission migration assumes integer primary
// keys and creates model_has_roles.model_id / model_has_permissions.
// model_id as unsignedBigInteger. Our User model uses ULID primary
// keys (26-char strings), which don't fit — MySQL truncates them,
// producing "Data truncated for column 'model_id'" on assignRole().
// This widens both columns to a string, wide enough for a ULID (26)
// or a UUID (36) in case any future morph-able model uses either.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE model_has_roles MODIFY model_id VARCHAR(36) NOT NULL');
        DB::statement('ALTER TABLE model_has_permissions MODIFY model_id VARCHAR(36) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE model_has_roles MODIFY model_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE model_has_permissions MODIFY model_id BIGINT UNSIGNED NOT NULL');
    }
};
