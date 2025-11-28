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
        if (!Schema::hasColumn('entries', 'exam_scheduled')) {
            Schema::table('entries', function (Blueprint $table) {
                $table->boolean('exam_scheduled')->default(false);
                $table->dateTime('exam_scheduled_date')->nullable();
                $table->boolean('exam_ready')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entries', function (Blueprint $table) {
            $table->dropColumn(['exam_scheduled', 'exam_scheduled_date', 'exam_ready']);
        });
    }
};
