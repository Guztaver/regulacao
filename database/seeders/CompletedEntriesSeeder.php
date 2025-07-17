<?php

namespace Database\Seeders;

use App\Models\Entry;
use App\Models\Patient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompletedEntriesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     *
     * WARNING: This seeder creates test/demo data only.
     * DO NOT run in production environment.
     *
     * Usage: php artisan db:seed --class=CompletedEntriesSeeder
     */
    public function run(): void
    {
        // WARNING: This creates DEMO DATA ONLY - not for production use
        $this->command->warn('⚠️  This seeder creates test data only. Do not use in production!');

        if (!$this->command->confirm('Do you want to create demo patients and entries?')) {
            $this->command->info('Seeder cancelled by user.');
            return;
        }

        // Create some test patients first
        $patients = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890'
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+1234567891'
            ],
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@example.com',
                'phone' => '+1234567892'
            ]
        ];

        $createdPatients = [];
        foreach ($patients as $patientData) {
            $patient = Patient::firstOrCreate(
                ['email' => $patientData['email']],
                $patientData
            );
            $createdPatients[] = $patient;
        }

        // Create completed entries
        $completedEntries = [
            [
                'title' => 'Blood pressure monitoring completed',
                'completed' => true,
                'created_at' => now()->subDays(5),
            ],
            [
                'title' => 'Diabetes consultation finished',
                'completed' => true,
                'created_at' => now()->subDays(3),
            ],
            [
                'title' => 'X-ray examination completed',
                'completed' => true,
                'created_at' => now()->subDays(2),
            ],
            [
                'title' => 'Lab results reviewed',
                'completed' => true,
                'created_at' => now()->subDays(1),
            ],
            [
                'title' => 'Physical therapy session completed',
                'completed' => true,
                'created_at' => now()->subHours(12),
            ]
        ];

        foreach ($completedEntries as $entryData) {
            $patient = $createdPatients[array_rand($createdPatients)];

            Entry::create([
                'patient_id' => $patient->id,
                'title' => $entryData['title'],
                'completed' => $entryData['completed'],
                'created_at' => $entryData['created_at'],
                'updated_at' => $entryData['created_at'],
            ]);
        }

        // Create some incomplete entries too
        $incompleteEntries = [
            'Cardiology appointment scheduled',
            'MRI scan pending',
            'Follow-up consultation needed'
        ];

        foreach ($incompleteEntries as $title) {
            $patient = $createdPatients[array_rand($createdPatients)];

            Entry::create([
                'patient_id' => $patient->id,
                'title' => $title,
                'completed' => false,
            ]);
        }

        $this->command->info('✅ Demo data seeder finished!');
        $this->command->info('Created ' . count($completedEntries) . ' completed entries');
        $this->command->info('Created ' . count($incompleteEntries) . ' incomplete entries');
        $this->command->warn('⚠️  Remember: This is test data only!');
    }
}
