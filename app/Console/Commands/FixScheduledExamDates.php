<?php

namespace App\Console\Commands;

use App\Models\Entry;
use App\Models\EntryStatus;
use App\Models\EntryStatusTransition;
use Illuminate\Console\Command;

class FixScheduledExamDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:scheduled-exam-dates {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix scheduled exam entries that have empty metadata by setting scheduled_date to transition date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Fixing scheduled exam dates with missing metadata...');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get scheduled status
        $scheduledStatus = EntryStatus::where('slug', EntryStatus::EXAM_SCHEDULED)->first();
        if (!$scheduledStatus) {
            $this->error('Exam scheduled status not found!');
            return 1;
        }

        // Find transitions to exam_scheduled status with empty metadata
        $brokenTransitions = EntryStatusTransition::where('to_status_id', $scheduledStatus->id)
            ->where(function($query) {
                $query->whereNull('metadata')
                      ->orWhere('metadata', '[]')
                      ->orWhere('metadata', '{}');
            })
            ->with(['entry.patient'])
            ->get();

        if ($brokenTransitions->isEmpty()) {
            $this->info('No broken scheduled exam transitions found.');
            return 0;
        }

        $this->info("Found {$brokenTransitions->count()} transitions with missing scheduled_date metadata");

        $fixed = 0;
        foreach ($brokenTransitions as $transition) {
            $entry = $transition->entry;
            $patient = $entry->patient;

            $this->info("Entry: {$entry->id} - Patient: {$patient->name} - Transition Date: {$transition->created_at}");

            if (!$isDryRun) {
                // Set the scheduled_date to the transition creation date
                $transition->metadata = [
                    'scheduled_date' => $transition->created_at->format('Y-m-d'),
                    'fixed_by_command' => true,
                    'original_transition_date' => $transition->created_at->toISOString()
                ];
                $transition->save();
                $fixed++;
                $this->line("  ✓ Fixed: Set scheduled_date to {$transition->created_at->format('Y-m-d')}");
            } else {
                $this->line("  → Would set scheduled_date to {$transition->created_at->format('Y-m-d')}");
            }
        }

        if ($isDryRun) {
            $this->info("\nDry run complete. Run without --dry-run to apply fixes.");
        } else {
            $this->info("\nFixed {$fixed} transitions.");
            $this->info('You can now check the scheduled entries - they should show proper dates instead of N/A.');
        }

        return 0;
    }
}
