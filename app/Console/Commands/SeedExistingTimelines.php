<?php

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryTimeline;
use Illuminate\Console\Command;

class SeedExistingTimelines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timeline:seed {--force : Force seeding even if timeline entries exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed timeline data for existing entries that don\'t have timeline records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting timeline seeding for existing entries...');

        $entries = Entry::with('createdBy')->get();
        $seededCount = 0;

        foreach ($entries as $entry) {
            // Check if entry already has timeline data
            if (!$this->option('force') && $entry->timeline()->exists()) {
                $this->line("Entry {$entry->id} already has timeline data, skipping...");
                continue;
            }

            // If force option is used, delete existing timeline data
            if ($this->option('force')) {
                $entry->timeline()->delete();
                $this->line("Cleared existing timeline data for entry {$entry->id}");
            }

            // Create initial creation timeline entry
            EntryTimeline::create([
                'entry_id' => $entry->id,
                'user_id' => $entry->created_by,
                'action' => EntryTimeline::ACTION_CREATED,
                'description' => 'Entry created',
                'metadata' => ['title' => $entry->title],
                'performed_at' => $entry->created_at,
            ]);

            // If entry is completed, add completion timeline
            if ($entry->completed) {
                EntryTimeline::create([
                    'entry_id' => $entry->id,
                    'user_id' => $entry->created_by,
                    'action' => EntryTimeline::ACTION_COMPLETED,
                    'description' => 'Entry marked as completed',
                    'metadata' => [],
                    'performed_at' => $entry->updated_at,
                ]);
            }

            // If exam is scheduled, add scheduling timeline
            if ($entry->exam_scheduled && $entry->exam_scheduled_date) {
                EntryTimeline::create([
                    'entry_id' => $entry->id,
                    'user_id' => $entry->created_by,
                    'action' => EntryTimeline::ACTION_EXAM_SCHEDULED,
                    'description' => 'Exam scheduled',
                    'metadata' => ['scheduled_date' => $entry->exam_scheduled_date->toDateString()],
                    'performed_at' => $entry->updated_at,
                ]);
            }

            // If exam is ready, add ready timeline
            if ($entry->exam_ready) {
                EntryTimeline::create([
                    'entry_id' => $entry->id,
                    'user_id' => $entry->created_by,
                    'action' => EntryTimeline::ACTION_EXAM_READY,
                    'description' => 'Exam marked as ready',
                    'metadata' => [],
                    'performed_at' => $entry->updated_at,
                ]);
            }

            $seededCount++;
            $this->line("Seeded timeline for entry {$entry->id}");
        }

        $this->info("Timeline seeding completed! Processed {$seededCount} entries.");
    }
}
