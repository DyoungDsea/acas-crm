<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic: an activity (call, meeting, note) can attach to
        // a Contact, a Deal, an Order — anything. ulidMorphs keeps the
        // foreign key type consistent with the ulid primary keys above.
        Schema::create('activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->ulidMorphs('subject'); // subject_id + subject_type
            $table->string('type'); // call, meeting, note, email
            $table->text('description')->nullable();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
