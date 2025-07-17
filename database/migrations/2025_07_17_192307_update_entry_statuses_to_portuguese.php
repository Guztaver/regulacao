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
        // Update status names to Portuguese
        DB::table('entry_statuses')->where('slug', 'pending')->update([
            'name' => 'Pendente',
            'description' => 'Entrada pendente aguardando ação',
            'updated_at' => now(),
        ]);

        DB::table('entry_statuses')->where('slug', 'exam_scheduled')->update([
            'name' => 'Exame Agendado',
            'description' => 'Exame foi agendado para o paciente',
            'updated_at' => now(),
        ]);

        DB::table('entry_statuses')->where('slug', 'exam_ready')->update([
            'name' => 'Exame Pronto',
            'description' => 'Resultados do exame estão prontos para revisão',
            'updated_at' => now(),
        ]);

        DB::table('entry_statuses')->where('slug', 'completed')->update([
            'name' => 'Concluído',
            'description' => 'Entrada foi concluída com sucesso',
            'updated_at' => now(),
        ]);

        DB::table('entry_statuses')->where('slug', 'cancelled')->update([
            'name' => 'Cancelado',
            'description' => 'Entrada foi cancelada',
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to English names
        DB::table('entry_statuses')->where('slug', 'pending')->update([
            'name' => 'Pending',
            'description' => 'Entry is pending and awaiting action',
            'updated_at' => now(),
        ]);

        DB::table('entry_statuses')->where('slug', 'exam_scheduled')->update([
            'name' => 'Exam Scheduled',
            'description' => 'Exam has been scheduled for the patient',
            'updated_at' => now(),
        ]);

        DB::table('entry_statuses')->where('slug', 'exam_ready')->update([
            'name' => 'Exam Ready',
            'description' => 'Exam results are ready for review',
            'updated_at' => now(),
        ]);

        DB::table('entry_statuses')->where('slug', 'completed')->update([
            'name' => 'Completed',
            'description' => 'Entry has been completed successfully',
            'updated_at' => now(),
        ]);

        DB::table('entry_statuses')->where('slug', 'cancelled')->update([
            'name' => 'Cancelled',
            'description' => 'Entry has been cancelled',
            'updated_at' => now(),
        ]);
    }
};
