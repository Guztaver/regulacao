<?php

namespace Database\Seeders;

use App\Models\Entry;
use App\Models\EntryDocument;
use App\Models\EntryStatus;
use App\Models\EntryStatusTransition;
use App\Models\EntryTimeline;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PrintTestSeeder extends Seeder
{
    /**
     * Run the database seeder.
     *
     * WARNING: This seeder creates test/demo data only.
     * DO NOT run in production environment.
     *
     * Usage: php artisan db:seed --class=PrintTestSeeder
     */
    public function run(): void
    {
        // WARNING: This creates DEMO DATA ONLY - not for production use
        $this->command->warn('⚠️  This seeder creates test data only. Do not use in production!');

        if (!$this->command->confirm('Do you want to create demo data for print testing?')) {
            $this->command->info('Seeder cancelled by user.');
            return;
        }

        // Create a test user if none exists
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Create test patient
        $patient = Patient::create([
            'name' => 'João Silva Santos',
            'phone' => '(11) 98765-4321',
            'sus_number' => '123456789012345',
            'created_by' => $user->id,
        ]);

        // Create entry statuses if they don't exist
        $statuses = [
            ['name' => 'Pendente', 'slug' => 'pending', 'color' => '#FFC107', 'is_final' => false, 'sort_order' => 1],
            ['name' => 'Exame Agendado', 'slug' => 'exam_scheduled', 'color' => '#28A745', 'is_final' => false, 'sort_order' => 2],
            ['name' => 'Exame Pronto', 'slug' => 'exam_ready', 'color' => '#007BFF', 'is_final' => false, 'sort_order' => 3],
            ['name' => 'Concluído', 'slug' => 'completed', 'color' => '#17A2B8', 'is_final' => true, 'sort_order' => 4],
            ['name' => 'Cancelado', 'slug' => 'cancelled', 'color' => '#DC3545', 'is_final' => true, 'sort_order' => 5],
        ];

        foreach ($statuses as $statusData) {
            EntryStatus::firstOrCreate(
                ['slug' => $statusData['slug']],
                $statusData
            );
        }

        // Get status objects
        $pendingStatus = EntryStatus::where('slug', 'pending')->first();
        $examScheduledStatus = EntryStatus::where('slug', 'exam_scheduled')->first();
        $examReadyStatus = EntryStatus::where('slug', 'exam_ready')->first();
        $completedStatus = EntryStatus::where('slug', 'completed')->first();

        // Create comprehensive test entry
        $entry = Entry::create([
            'patient_id' => $patient->id,
            'title' => 'Exame de Ressonância Magnética - Região Cervical',
            'current_status_id' => $completedStatus->id,
            'created_by' => $user->id,
        ]);

        // Create status transitions with realistic timeline
        $baseTime = now()->subDays(10);

        // Initial creation
        EntryStatusTransition::create([
            'entry_id' => $entry->id,
            'from_status_id' => null,
            'to_status_id' => $pendingStatus->id,
            'user_id' => $user->id,
            'reason' => 'Entrada criada no sistema',
            'transitioned_at' => $baseTime,
            'metadata' => ['title' => $entry->title],
        ]);

        // Schedule exam
        EntryStatusTransition::create([
            'entry_id' => $entry->id,
            'from_status_id' => $pendingStatus->id,
            'to_status_id' => $examScheduledStatus->id,
            'user_id' => $user->id,
            'reason' => 'Exame agendado conforme disponibilidade',
            'scheduled_date' => now()->addDays(3),
            'transitioned_at' => $baseTime->addDays(2),
            'metadata' => [
                'scheduled_by' => $user->name,
                'facility' => 'Centro de Diagnóstico por Imagem',
                'equipment' => 'Ressonância Magnética 1.5T',
            ],
        ]);

        // Mark exam ready
        EntryStatusTransition::create([
            'entry_id' => $entry->id,
            'from_status_id' => $examScheduledStatus->id,
            'to_status_id' => $examReadyStatus->id,
            'user_id' => $user->id,
            'reason' => 'Exame realizado, resultados disponíveis',
            'transitioned_at' => $baseTime->addDays(5),
            'metadata' => [
                'exam_date' => now()->addDays(3)->format('Y-m-d'),
                'report_available' => true,
                'images_available' => true,
            ],
        ]);

        // Complete entry
        EntryStatusTransition::create([
            'entry_id' => $entry->id,
            'from_status_id' => $examReadyStatus->id,
            'to_status_id' => $completedStatus->id,
            'user_id' => $user->id,
            'reason' => 'Laudo entregue ao paciente e médico solicitante',
            'transitioned_at' => $baseTime->addDays(7),
            'metadata' => [
                'delivered_to' => 'Dr. Maria Santos - CRM 123456',
                'delivery_method' => 'Sistema eletrônico + cópia física',
                'patient_notified' => true,
            ],
        ]);

        // Create timeline entries
        $timelineEntries = [
            [
                'action' => 'Entrada Criada',
                'description' => 'Nova entrada registrada no sistema',
                'performed_at' => $baseTime,
                'metadata' => ['system_user' => $user->name],
            ],
            [
                'action' => 'Documentos Anexados',
                'description' => 'Pedido médico e documentos do paciente anexados',
                'performed_at' => $baseTime->addHours(2),
                'metadata' => ['documents_count' => 2],
            ],
            [
                'action' => 'Agendamento Realizado',
                'description' => 'Exame agendado para ' . now()->addDays(3)->format('d/m/Y'),
                'performed_at' => $baseTime->addDays(2),
                'metadata' => ['facility' => 'Centro de Diagnóstico'],
            ],
            [
                'action' => 'Exame Realizado',
                'description' => 'Paciente compareceu e exame foi realizado com sucesso',
                'performed_at' => $baseTime->addDays(5),
                'metadata' => ['technician' => 'Ana Paula Silva'],
            ],
            [
                'action' => 'Laudo Disponível',
                'description' => 'Laudo médico foi elaborado e está disponível',
                'performed_at' => $baseTime->addDays(6),
                'metadata' => ['radiologist' => 'Dr. Carlos Oliveira'],
            ],
            [
                'action' => 'Entrega Concluída',
                'description' => 'Resultados entregues ao médico solicitante e paciente',
                'performed_at' => $baseTime->addDays(7),
                'metadata' => ['delivery_confirmed' => true],
            ],
        ];

        foreach ($timelineEntries as $timelineData) {
            EntryTimeline::create([
                'entry_id' => $entry->id,
                'user_id' => $user->id,
                'action' => $timelineData['action'],
                'description' => $timelineData['description'],
                'performed_at' => $timelineData['performed_at'],
                'metadata' => $timelineData['metadata'],
            ]);
        }

        // Create sample documents
        $documents = [
            [
                'original_name' => 'pedido_medico.pdf',
                'document_type' => 'medical_request',
                'description' => 'Pedido médico para ressonância magnética cervical',
                'file_size' => 245760, // 240KB
                'mime_type' => 'application/pdf',
            ],
            [
                'original_name' => 'documento_identidade.jpg',
                'document_type' => 'identification',
                'description' => 'Documento de identidade do paciente',
                'file_size' => 512000, // 500KB
                'mime_type' => 'image/jpeg',
            ],
            [
                'original_name' => 'cartao_sus.pdf',
                'document_type' => 'sus_card',
                'description' => 'Cartão Nacional de Saúde do paciente',
                'file_size' => 180000, // 175KB
                'mime_type' => 'application/pdf',
            ],
        ];

        foreach ($documents as $docData) {
            // Create a fake file path
            $filePath = 'entry_documents/' . $entry->id . '/' . uniqid() . '_' . $docData['original_name'];

            EntryDocument::create([
                'entry_id' => $entry->id,
                'original_name' => $docData['original_name'],
                'file_path' => $filePath,
                'file_size' => $docData['file_size'],
                'mime_type' => $docData['mime_type'],
                'document_type' => $docData['document_type'],
                'description' => $docData['description'],
                'created_at' => $baseTime->addHours(rand(1, 48)),
            ]);
        }

        $this->command->info('✅ Test data created successfully!');
        $this->command->info("📋 Created entry with ID: {$entry->id}");
        $this->command->info("👤 Patient: {$patient->name} (ID: {$patient->id})");
        $this->command->info("📄 Documents: " . count($documents));
        $this->command->info("🔄 Status transitions: 4");
        $this->command->info("📅 Timeline entries: " . count($timelineEntries));
        $this->command->info('');
        $this->command->info("🖨️  You can now test the print functionality with entry ID: {$entry->id}");
        $this->command->info("🌐 Print URL: /api/entries/{$entry->id}/print");
    }
}
