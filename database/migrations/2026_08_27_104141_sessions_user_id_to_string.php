<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Same root cause as the earlier model_has_roles fix: Laravel's
// default sessions table migration assumes an auto-increment user
// id and creates sessions.user_id as unsignedBigInteger via
// foreignId(). Our User model uses ULID primary keys (26-char
// strings), which don't fit — the write silently fails during
// session termination (after the response is already sent), so
// nothing ever appears to error, but the session simply never
// persists for a logged-in user. This is why flash data worked on
// guest pages (user_id is NULL there — no truncation) and broke
// the instant you were authenticated.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            DB::statement('ALTER TABLE sessions MODIFY user_id VARCHAR(36) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sessions')) {
            DB::statement('ALTER TABLE sessions MODIFY user_id BIGINT UNSIGNED NULL');
        }
    }
};
