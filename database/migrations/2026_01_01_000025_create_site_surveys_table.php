<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A project can have multiple surveys over time (initial survey,
        // follow-up after site changes), so this is its own table rather
        // than columns on solar_projects.
        Schema::create('site_surveys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('solar_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('surveyed_by')->constrained('users')->cascadeOnDelete();
            $table->date('survey_date');
            $table->text('findings')->nullable();
            $table->string('status')->default('pending'); // pending, completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_surveys');
    }
};
