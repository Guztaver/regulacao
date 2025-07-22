<?php

namespace App\Models;

use Database\Factories\EntryFactory;
use Faker\Core\Uuid;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @property string $id
 * @property string $patient_id
 * @property string $title
 * @property string|null $brought_by
 * @property int $current_status_id
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Patient $patient
 * @property-read \App\Models\User $createdBy
 * @property-read \App\Models\EntryStatus $currentStatus
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EntryTimeline[] $timeline
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EntryStatusTransition[] $statusTransitions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EntryDocument[] $documents
 * @property-read \App\Models\EntryStatusTransition|null $latestTransition
 * @property-read string|null $scheduled_date
 * @property-read string|null $scheduled_exam_date
 * @property-read bool $exam_scheduled
 * @property-read bool $exam_ready
 * @property-read bool $completed
 */
class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'title',
        'brought_by',
        'current_status_id',
        'created_by',
    ];

    /**
     * Validation rules for the model
     */
    public static function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'title' => 'required|string|max:255',
            'brought_by' => 'nullable|string|max:255',
            'current_status_id' => 'required|exists:entry_statuses,id',
            'created_by' => 'required|exists:users,id',
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'scheduled_exam_date',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array<string>
     */
    protected $with = [];

    public $timestamps = true;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(EntryStatus::class, 'current_status_id');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(EntryTimeline::class);
    }

    public function statusTransitions(): HasMany
    {
        return $this->hasMany(EntryStatusTransition::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EntryDocument::class);
    }

    public function latestTransition(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EntryStatusTransition::class);
    }

    /**
     * Transition this entry to a new status.
     */
    public function transitionTo(int $statusId, ?string $reason = null, array $metadata = []): bool
    {
        $newStatus = EntryStatus::find($statusId);
        $currentStatus = $this->currentStatus;

        Log::info('Entry transitionTo called', [
            'entry_id' => $this->id,
            'from_status_id' => $this->current_status_id,
            'from_status_slug' => $currentStatus?->slug,
            'to_status_id' => $statusId,
            'to_status_slug' => $newStatus?->slug,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);

        if (! $newStatus) {
            Log::error('Status not found', ['status_id' => $statusId]);
            throw new \InvalidArgumentException("Status with ID {$statusId} not found");
        }

        // Special handling for exam_scheduled status
        if ($newStatus->slug === EntryStatus::EXAM_SCHEDULED) {
            if (empty($metadata['scheduled_date'])) {
                throw new \InvalidArgumentException('scheduled_date is required in metadata when transitioning to exam_scheduled status');
            }
        }

        // Check if transition is allowed
        if ($currentStatus && ! $currentStatus->canTransitionTo($newStatus)) {
            Log::error('Transition not allowed', [
                'from' => $currentStatus->slug,
                'to' => $newStatus->slug,
                'from_is_final' => $currentStatus->is_final,
            ]);
            throw new \InvalidArgumentException("Cannot transition from {$currentStatus->name} to {$newStatus->name}");
        }

        // Create the transition record
        try {
            EntryStatusTransition::createTransition(
                $this->id,
                $this->current_status_id,
                $statusId,
                null,
                $reason,
                $metadata
            );
            Log::info('Transition record created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create transition record', ['error' => $e->getMessage()]);
            throw $e;
        }

        // Update the current status
        $this->current_status_id = $statusId;
        $saved = $this->save();

        Log::info('Entry status updated', [
            'entry_id' => $this->id,
            'new_status_id' => $statusId,
            'saved' => $saved,
        ]);

        return $saved;
    }

    /**
     * Transition to completed status.
     */
    public function markAsCompleted(?string $reason = null): bool
    {
        $completedStatus = EntryStatus::findBySlugOrFail(EntryStatus::COMPLETED);

        return $this->transitionTo($completedStatus->id, $reason ?? 'Entrada marcada como concluída');
    }

    /**
     * Schedule an exam for this entry.
     */
    public function scheduleExam(?string $scheduledDate = null, ?string $reason = null): bool
    {
        $examScheduledStatus = EntryStatus::findBySlugOrFail(EntryStatus::EXAM_SCHEDULED);

        // If already in exam_scheduled status, update the transition metadata instead of creating new transition
        if ($this->hasStatus(EntryStatus::EXAM_SCHEDULED)) {
            // Update the most recent exam_scheduled transition with new scheduled date
            $latestTransition = $this->statusTransitions()
                ->whereHas('toStatus', function ($query) {
                    $query->where('slug', EntryStatus::EXAM_SCHEDULED);
                })
                ->first();

            if ($latestTransition) {
                $metadata = $latestTransition->metadata ?? [];
                $metadata['scheduled_date'] = $scheduledDate;
                $metadata['updated_at'] = now()->toISOString();
                $latestTransition->update([
                    'metadata' => $metadata,
                    'reason' => $reason ?? 'Exame reagendado',
                ]);

                // Log the rescheduling action
                EntryTimeline::logAction(
                    $this->id,
                    Auth::id() ?? $this->created_by,
                    EntryTimeline::ACTION_EXAM_SCHEDULED,
                    $reason ?? 'Exame reagendado',
                    ['scheduled_date' => $scheduledDate, 'rescheduled' => true]
                );

                return true;
            }
        }

        return $this->transitionTo(
            $examScheduledStatus->id,
            $reason ?? 'Exame agendado',
            ['scheduled_date' => $scheduledDate]
        );
    }

    /**
     * Mark exam as ready.
     */
    public function markExamReady(?string $reason = null): bool
    {
        $examReadyStatus = EntryStatus::findBySlugOrFail(EntryStatus::EXAM_READY);

        return $this->transitionTo($examReadyStatus->id, $reason ?? 'Exam results are ready');
    }

    /**
     * Cancel this entry.
     */
    public function cancel(?string $reason = null): bool
    {
        $cancelledStatus = EntryStatus::findBySlugOrFail(EntryStatus::CANCELLED);

        return $this->transitionTo($cancelledStatus->id, $reason ?? 'Entrada cancelada');
    }

    /**
     * Check if entry is in a specific status.
     */
    public function hasStatus(string $statusSlug): bool
    {
        return $this->currentStatus?->slug === $statusSlug;
    }

    /**
     * Check if entry is completed.
     */
    public function isCompleted(): bool
    {
        return $this->hasStatus(EntryStatus::COMPLETED);
    }

    /**
     * Check if entry is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->hasStatus(EntryStatus::CANCELLED);
    }

    /**
     * Check if entry has exam scheduled.
     */
    public function hasExamScheduled(): bool
    {
        return in_array($this->currentStatus?->slug, [
            EntryStatus::EXAM_SCHEDULED,
            EntryStatus::EXAM_READY,
            EntryStatus::COMPLETED,
        ]);
    }

    /**
     * Get the scheduled exam date from transitions.
     */
    public function getScheduledExamDate(): ?string
    {
        /** @var \App\Models\EntryStatusTransition|null $examTransition */
        $examTransition = $this->statusTransitions()
            ->whereHas('toStatus', function ($query) {
                $query->where('slug', EntryStatus::EXAM_SCHEDULED);
            })
            ->latest()
            ->first();

        return $examTransition?->scheduled_date;
    }

    /**
     * Get the next possible statuses for this entry.
     */
    public function getNextStatuses(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->currentStatus?->getNextStatuses() ?? collect();
    }

    /**
     * Get the scheduled exam date accessor for the frontend.
     */
    public function getScheduledExamDateAttribute(): ?string
    {
        return $this->getScheduledExamDate();
    }

    /**
     * Get the scheduled date accessor.
     */
    public function getScheduledDateAttribute(): ?string
    {
        return $this->getScheduledExamDate();
    }

    /**
     * Get the exam scheduled accessor.
     */
    public function getExamScheduledAttribute(): bool
    {
        return $this->hasExamScheduled();
    }

    /**
     * Get the exam ready accessor.
     */
    public function getExamReadyAttribute(): bool
    {
        return $this->hasStatus(EntryStatus::EXAM_READY);
    }

    /**
     * Get the completed accessor.
     */
    public function getCompletedAttribute(): bool
    {
        return $this->isCompleted();
    }

    /**
     * Boot the model and add model events
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure created_by is always set when creating
        static::creating(function ($entry) {
            if (empty($entry->created_by)) {
                throw new \InvalidArgumentException('created_by is required and cannot be null');
            }

            // Set default status if not provided
            if (empty($entry->current_status_id)) {
                try {
                    $defaultStatus = EntryStatus::getDefaultStatus();
                    $entry->current_status_id = $defaultStatus->id;
                } catch (\RuntimeException $e) {
                    throw new \InvalidArgumentException('Cannot create entry: '.$e->getMessage());
                }
            }
        });

        // Log entry creation
        static::created(function ($entry) {
            if (Auth::check()) {
                // Create initial status transition
                EntryStatusTransition::createTransition(
                    $entry->id,
                    null,
                    $entry->current_status_id,
                    $entry->created_by,
                    'Entrada criada',
                    ['title' => $entry->title]
                );

                // Also log in the old timeline for backward compatibility
                EntryTimeline::logAction(
                    $entry->id,
                    $entry->created_by,
                    EntryTimeline::ACTION_CREATED,
                    'Entrada criada',
                    ['title' => $entry->title, 'brought_by' => $entry->brought_by]
                );
            }
        });

        // Prevent updates that would set created_by to null
        static::updating(function ($entry) {
            if (empty($entry->created_by)) {
                throw new \InvalidArgumentException('created_by is required and cannot be null');
            }

            if (empty($entry->current_status_id)) {
                throw new \InvalidArgumentException('current_status_id is required and cannot be null');
            }
        });

        // Log entry updates
        static::updated(function ($entry) {
            if (Auth::check()) {
                $changes = $entry->getChanges();

                // Skip logging if only timestamps changed
                unset($changes['updated_at']);

                if (! empty($changes)) {
                    // Status transitions are handled by the transitionTo method
                    // Log other field changes
                    $statusFields = ['current_status_id'];
                    $nonStatusChanges = array_diff_key($changes, array_flip($statusFields));

                    if (! empty($nonStatusChanges)) {
                        EntryTimeline::logAction(
                            $entry->id,
                            Auth::id(),
                            EntryTimeline::ACTION_UPDATED,
                            'Entrada atualizada',
                            ['changes' => $nonStatusChanges]
                        );
                    }
                }
            }
        });

        // Log entry deletion
        static::deleting(function ($entry) {
            if (Auth::check()) {
                EntryTimeline::logAction(
                    $entry->id,
                    Auth::id(),
                    EntryTimeline::ACTION_DELETED,
                    'Entrada excluída',
                    ['title' => $entry->title, 'brought_by' => $entry->brought_by]
                );
            }
        });
    }
}
