<?php

namespace Database\Seeders;

use App\Models\Entry;
use App\Models\EntryDocument;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EntryDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all entries
        $entries = Entry::all();

        if ($entries->isEmpty()) {
            $this->command->info('No entries found. Please run EntrySeeder first.');
            return;
        }

        $this->command->info('Creating entry documents...');

        // Create documents for each entry
        foreach ($entries as $entry) {
            // Random number of documents per entry (0-5)
            $documentCount = rand(0, 5);

            if ($documentCount > 0) {
                // Create different types of documents
                $documentTypes = [
                    'medical_request',
                    'exam_result',
                    'medical_report',
                    'prescription',
                    'authorization',
                    'referral',
                    'lab_result',
                    'imaging_result',
                    'consultation_note',
                    'other'
                ];

                for ($i = 0; $i < $documentCount; $i++) {
                    $documentType = $documentTypes[array_rand($documentTypes)];

                    EntryDocument::factory()
                        ->for($entry)
                        ->create([
                            'document_type' => $documentType,
                            'description' => $this->getDescriptionForType($documentType),
                        ]);
                }
            }
        }

        $totalDocuments = EntryDocument::count();
        $this->command->info("Created {$totalDocuments} entry documents successfully!");
    }

    /**
     * Get appropriate description for document type.
     */
    private function getDescriptionForType(string $type): string
    {
        return match ($type) {
            'medical_request' => 'Solicitação médica para procedimento especializado',
            'exam_result' => 'Resultado de exame complementar',
            'medical_report' => 'Relatório médico detalhado',
            'prescription' => 'Prescrição médica para tratamento',
            'authorization' => 'Autorização para procedimento',
            'referral' => 'Encaminhamento para especialista',
            'lab_result' => 'Resultado de exame laboratorial',
            'imaging_result' => 'Resultado de exame de imagem',
            'consultation_note' => 'Nota de consulta médica',
            'discharge_summary' => 'Resumo de alta hospitalar',
            default => 'Documento relacionado à entrada',
        };
    }
}
