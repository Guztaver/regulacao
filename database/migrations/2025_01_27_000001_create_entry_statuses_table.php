<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entry_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->string('color', 7)->default('#6B7280'); // Hex color for UI
            $table->string('description')->nullable();
            $table->boolean('is_final')->default(false); // Indicates if this is a terminal status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Insert default statuses
        DB::table('entry_statuses')->insert([
            [
                'name' => 'Pending',
                'slug' => 'pending',
                'color' => '#F59E0B',
                'description' => 'Entry is pending and awaiting action',
                'is_final' => false,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Exam Scheduled',
                'slug' => 'exam_scheduled',
                'color' => '#3B82F6',
                'description' => 'Exam has been scheduled for the patient',
                'is_final' => false,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Exam Ready',
                'slug' => 'exam_ready',
                'color' => '#8B5CF6',
                'description' => 'Exam results are ready for review',
                'is_final' => false,
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Completed',
                'slug' => 'completed',
                'color' => '#10B981',
                'description' => 'Entry has been completed successfully',
                'is_final' => true,
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cancelled',
                'slug' => 'cancelled',
                'color' => '#EF4444',
                'description' => 'Entry has been cancelled',
                'is_final' => true,
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_statuses');
    }
};
