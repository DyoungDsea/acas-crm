<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The original deals.pipeline_stage_id FK used cascadeOnDelete() —
// meaning deleting a pipeline stage (a configuration item) would
// silently delete every real deal sitting in it. Now that the
// Pipelines page actually exposes a "delete stage" button to users,
// this needs to be a hard backstop, not just an app-level check:
// restrictOnDelete() makes MySQL itself refuse the delete while
// deals still reference that stage, on top of the friendly
// application-level message PipelinePageController shows first.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['pipeline_stage_id']);
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->foreign('pipeline_stage_id')->references('id')->on('pipeline_stages')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['pipeline_stage_id']);
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->foreign('pipeline_stage_id')->references('id')->on('pipeline_stages')->cascadeOnDelete();
        });
    }
};
