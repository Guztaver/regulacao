<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * WARNING: This creates test data. Do not run in production.
     */
    public function run(): void
    {
        // WARNING: This creates TEST DATA ONLY - not for production use
        $this->command->warn('⚠️  This seeder creates test data only. Do not use in production!');

        if (!$this->command->confirm('Do you want to create a test user?')) {
            $this->command->info('Seeder cancelled by user.');
            return;
        }

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->command->info('✅ Test user created: test@example.com');
        $this->command->warn('⚠️  Remember: This is test data only!');
    }
}
