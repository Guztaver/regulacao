<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('entry_timelines')) {
            Schema::create('entry_timelines', function (Blueprint $table) {
                $table->id();
                $table->uuid('entry_id');
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('action'); // created, updated, completed, exam_scheduled, exam_ready, etc.
                $table->text('description')->nullable(); // Human readable description
                $table->json('metadata')->nullable(); // Store additional data like scheduled dates, old/new values
                $table->timestamp('performed_at');
                $table->timestamps();

                $table->foreign('entry_id')->references('id')->on('entries')->cascadeOnDelete();
                $table->index(['entry_id', 'performed_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_timelines');
    }
};
