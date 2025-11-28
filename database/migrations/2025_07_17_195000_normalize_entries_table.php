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
        if (!Schema::hasColumn('entries', 'current_status_id')) {
            // First, add the current_status_id column
            Schema::table('entries', function (Blueprint $table) {
                $table->foreignId('current_status_id')->nullable()->constrained('entry_statuses');
            });

            // Migrate existing data based on boolean fields
            $this->migrateExistingData();

            // Make current_status_id non-nullable after migration
            Schema::table('entries', function (Blueprint $table) {
                $table->foreignId('current_status_id')->nullable(false)->change();
            });
        }

        if (Schema::hasColumn('entries', 'completed')) {
            // Remove the old boolean and date columns
            Schema::table('entries', function (Blueprint $table) {
                $table->dropColumn([
                    'completed',
                    'exam_scheduled',
                    'exam_scheduled_date',
                    'exam_ready',
                ]);
            });
        }
    }

    /**
     * Migrate existing boolean data to status-based system
     */
    private function migrateExistingData(): void
    {
        // Get status IDs
        $pendingStatusId = DB::table('entry_statuses')->where('slug', 'pending')->value('id');
        $examScheduledStatusId = DB::table('entry_statuses')->where('slug', 'exam_scheduled')->value('id');
        $examReadyStatusId = DB::table('entry_statuses')->where('slug', 'exam_ready')->value('id');
        $completedStatusId = DB::table('entry_statuses')->where('slug', 'completed')->value('id');

        // Get all entries that need migration
        $entries = DB::table('entries')->get();

        foreach ($entries as $entry) {
            $currentStatusId = $pendingStatusId; // Default status
            $transitions = [];

            // Determine the current status based on boolean fields
            if ($entry->completed) {
                $currentStatusId = $completedStatusId;
            } elseif ($entry->exam_ready) {
                $currentStatusId = $examReadyStatusId;
            } elseif ($entry->exam_scheduled) {
                $currentStatusId = $examScheduledStatusId;
            }

            // Update the entry with the current status
            DB::table('entries')
                ->where('id', $entry->id)
                ->update(['current_status_id' => $currentStatusId]);

            // Create status transitions based on the entry's history
            $transitionTime = $entry->created_at;

            // Initial transition to pending
            DB::table('entry_status_transitions')->insert([
                'entry_id' => $entry->id,
                'from_status_id' => null,
                'to_status_id' => $pendingStatusId,
                'user_id' => $entry->created_by,
                'reason' => 'Entry created',
                'metadata' => json_encode(['migrated_from_boolean_fields' => true]),
                'transitioned_at' => $transitionTime,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add transitions based on current state
            if ($entry->exam_scheduled) {
                $examScheduledTime = $entry->exam_scheduled_date ?
                    $entry->exam_scheduled_date :
                    $entry->updated_at;

                DB::table('entry_status_transitions')->insert([
                    'entry_id' => $entry->id,
                    'from_status_id' => $pendingStatusId,
                    'to_status_id' => $examScheduledStatusId,
                    'user_id' => $entry->created_by,
                    'reason' => 'Exame agendado durante migração',
                    'metadata' => json_encode([
                        'scheduled_date' => $entry->exam_scheduled_date,
                        'migrated_from_boolean_fields' => true,
                    ]),
                    'transitioned_at' => $examScheduledTime,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($entry->exam_ready) {
                DB::table('entry_status_transitions')->insert([
                    'entry_id' => $entry->id,
                    'from_status_id' => $entry->exam_scheduled ? $examScheduledStatusId : $pendingStatusId,
                    'to_status_id' => $examReadyStatusId,
                    'user_id' => $entry->created_by,
                    'reason' => 'Exam ready during migration',
                    'metadata' => json_encode(['migrated_from_boolean_fields' => true]),
                    'transitioned_at' => $entry->updated_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($entry->completed) {
                $fromStatusId = $examReadyStatusId;
                if (! $entry->exam_ready && $entry->exam_scheduled) {
                    $fromStatusId = $examScheduledStatusId;
                } elseif (! $entry->exam_ready && ! $entry->exam_scheduled) {
                    $fromStatusId = $pendingStatusId;
                }

                DB::table('entry_status_transitions')->insert([
                    'entry_id' => $entry->id,
                    'from_status_id' => $fromStatusId,
                    'to_status_id' => $completedStatusId,
                    'user_id' => $entry->created_by,
                    'reason' => 'Entrada concluída durante migração',
                    'metadata' => json_encode(['migrated_from_boolean_fields' => true]),
                    'transitioned_at' => $entry->updated_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the old columns
        Schema::table('entries', function (Blueprint $table) {
            $table->boolean('completed')->default(false);
            $table->boolean('exam_scheduled')->default(false);
            $table->dateTime('exam_scheduled_date')->nullable();
            $table->boolean('exam_ready')->default(false);
        });

        // Migrate data back from status system to boolean fields
        $this->migrateDataBack();

        // Remove the status column
        Schema::table('entries', function (Blueprint $table) {
            $table->dropForeign(['current_status_id']);
            $table->dropColumn('current_status_id');
        });
    }

    /**
     * Migrate data back to boolean system (for rollback)
     */
    private function migrateDataBack(): void
    {
        $entries = DB::table('entries')
            ->join('entry_statuses', 'entries.current_status_id', '=', 'entry_statuses.id')
            ->select('entries.*', 'entry_statuses.slug as status_slug')
            ->get();

        foreach ($entries as $entry) {
            $updates = [
                'completed' => false,
                'exam_scheduled' => false,
                'exam_scheduled_date' => null,
                'exam_ready' => false,
            ];

            // Get the exam scheduled date from transitions if available
            $examScheduledTransition = DB::table('entry_status_transitions')
                ->join('entry_statuses', 'entry_status_transitions.to_status_id', '=', 'entry_statuses.id')
                ->where('entry_status_transitions.entry_id', $entry->id)
                ->where('entry_statuses.slug', 'exam_scheduled')
                ->first();

            if ($examScheduledTransition && $examScheduledTransition->metadata) {
                $metadata = json_decode($examScheduledTransition->metadata, true);
                if (isset($metadata['scheduled_date'])) {
                    $updates['exam_scheduled_date'] = $metadata['scheduled_date'];
                }
            }

            // Set boolean flags based on current status
            switch ($entry->status_slug) {
                case 'completed':
                    $updates['completed'] = true;
                    $updates['exam_ready'] = true;
                    $updates['exam_scheduled'] = true;
                    break;
                case 'exam_ready':
                    $updates['exam_ready'] = true;
                    $updates['exam_scheduled'] = true;
                    break;
                case 'exam_scheduled':
                    $updates['exam_scheduled'] = true;
                    break;
            }

            DB::table('entries')
                ->where('id', $entry->id)
                ->update($updates);
        }
    }
};
