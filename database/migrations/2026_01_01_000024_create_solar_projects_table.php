<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Helion Dynamics. A project is the umbrella — survey, install,
        // or ongoing consultancy — tied to a client contact. Orders/
        // payments/receipts already defined earlier attach to this via
        // the contact, so no separate billing table is needed here.
        Schema::create('solar_projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contact_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type'); // site_survey, installation, consultancy, solar_farm
            $table->string('status')->default('survey'); // survey, quoted, installing, completed
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solar_projects');
    }
};
