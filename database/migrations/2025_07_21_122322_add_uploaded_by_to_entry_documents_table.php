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
        Schema::table('entry_documents', function (Blueprint $table) {
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entry_documents', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropIndex(['uploaded_by']);
            $table->dropColumn('uploaded_by');
        });
    }
};
