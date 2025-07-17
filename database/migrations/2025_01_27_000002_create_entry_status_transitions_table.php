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
        Schema::create('entry_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('entry_id');
            $table->foreignId('from_status_id')->nullable()->constrained('entry_statuses');
            $table->foreignId('to_status_id')->constrained('entry_statuses');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('reason')->nullable(); // Optional reason for the transition
            $table->json('metadata')->nullable(); // Store additional data like scheduled dates, notes
            $table->timestamp('transitioned_at');
            $table->timestamps();

            $table->foreign('entry_id')->references('id')->on('entries')->cascadeOnDelete();
            $table->index(['entry_id', 'transitioned_at']);
            $table->index(['to_status_id', 'transitioned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_status_transitions');
    }
};
