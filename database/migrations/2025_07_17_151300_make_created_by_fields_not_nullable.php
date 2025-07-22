<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing NULL values to a default user (assuming user ID 1 exists)
        // You may need to adjust this based on your actual user IDs
        DB::statement('UPDATE entries SET created_by = 1 WHERE created_by IS NULL');
        DB::statement('UPDATE patients SET created_by = 1 WHERE created_by IS NULL');

        // Make created_by fields NOT NULL in entries table
        Schema::table('entries', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(false)->change();
        });

        // Make created_by fields NOT NULL in patients table
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(false)->change();
        });

        // Ensure critical fields in users table are NOT NULL (they should already be, but let's be explicit)
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });

        // Ensure critical fields in patients table are NOT NULL
        Schema::table('patients', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });

        // Ensure critical fields in entries table are NOT NULL
        Schema::table('entries', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->foreignUuid('patient_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert created_by fields to nullable in entries table
        Schema::table('entries', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
        });

        // Revert created_by fields to nullable in patients table
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->change();
        });

        // Note: We don't revert the other changes as they should remain NOT NULL for data integrity
    }
};
