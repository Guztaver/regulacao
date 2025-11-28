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
        if (!Schema::hasTable('entry_documents')) {
            Schema::create('entry_documents', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('entry_id');
                $table->string('original_name');
                $table->string('file_name');
                $table->string('file_path', 500);
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('file_size');
                $table->string('document_type')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();

                $table->foreign('entry_id')
                    ->references('id')
                    ->on('entries')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->index(['entry_id', 'created_at']);
                $table->index('document_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_documents');
    }
};
