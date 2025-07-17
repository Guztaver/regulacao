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
use App\Models\User;

/**
 * @property Uuid $patient_id
 * @property string $title
 * @property int $current_status_id
 * @property int $created_by
 */
class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'title',
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
        return $this->hasMany(EntryTimeline::class)->orderBy('performed_at', 'desc');
    }

    public function statusTransitions(): HasMany
    {
        return $this->hasMany(EntryStatusTransition::class)->with(['fromStatus', 'toStatus', 'user'])->latest();
    }

    public function latestTransition(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EntryStatusTransition::class)->with(['fromStatus', 'toStatus', 'user'])->latest();
    }

    /**
     * Transition this entry to a new status.
     */
    public function transitionTo(int $statusId, ?string $reason = null, array $metadata = []): bool
    {
        $newStatus = EntryStatus::find($statusId);
        $currentStatus = $this->currentStatus;

        if (!$newStatus) {
            throw new \InvalidArgumentException("Status with ID {$statusId} not found");
        }

        // Check if transition is allowed
        if ($currentStatus && !$currentStatus->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException("Cannot transition from {$currentStatus->name} to {$newStatus->name}");
        }

        // Create the transition record
        EntryStatusTransition::createTransition(
            $this->id,
            $this->current_status_id,
            $statusId,
            null,
            $reason,
            $metadata
        );

        // Update the current status
        $this->current_status_id = $statusId;
        return $this->save();
    }

    /**
     * Transition to completed status.
     */
    public function markAsCompleted(?string $reason = null): bool
    {
        $completedStatus = EntryStatus::findBySlug(EntryStatus::COMPLETED);
        return $this->transitionTo($completedStatus->id, $reason ?? 'Entry marked as completed');
    }

    /**
     * Schedule an exam for this entry.
     */
    public function scheduleExam(string $scheduledDate, ?string $reason = null): bool
    {
        $examScheduledStatus = EntryStatus::findBySlug(EntryStatus::EXAM_SCHEDULED);
        return $this->transitionTo(
            $examScheduledStatus->id,
            $reason ?? 'Exam scheduled',
            ['scheduled_date' => $scheduledDate]
        );
    }

    /**
     * Mark exam as ready.
     */
    public function markExamReady(?string $reason = null): bool
    {
        $examReadyStatus = EntryStatus::findBySlug(EntryStatus::EXAM_READY);
        return $this->transitionTo($examReadyStatus->id, $reason ?? 'Exam results are ready');
    }

    /**
     * Cancel this entry.
     */
    public function cancel(?string $reason = null): bool
    {
        $cancelledStatus = EntryStatus::findBySlug(EntryStatus::CANCELLED);
        return $this->transitionTo($cancelledStatus->id, $reason ?? 'Entry cancelled');
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
            EntryStatus::COMPLETED
        ]);
    }

    /**
     * Get the scheduled exam date from transitions.
     */
    public function getScheduledExamDate(): ?string
    {
        $examTransition = $this->statusTransitions()
            ->whereHas('toStatus', function ($query) {
                $query->where('slug', EntryStatus::EXAM_SCHEDULED);
            })
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
                $entry->current_status_id = EntryStatus::getDefaultStatus()->id;
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
                    'Entry created',
                    ['title' => $entry->title]
                );

                // Also log in the old timeline for backward compatibility
                EntryTimeline::logAction(
                    $entry->id,
                    $entry->created_by,
                    EntryTimeline::ACTION_CREATED,
                    'Entry created',
                    ['title' => $entry->title]
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

                if (!empty($changes)) {
                    // Status transitions are handled by the transitionTo method
                    // Log other field changes
                    $statusFields = ['current_status_id'];
                    $nonStatusChanges = array_diff_key($changes, array_flip($statusFields));

                    if (!empty($nonStatusChanges)) {
                        EntryTimeline::logAction(
                            $entry->id,
                            Auth::id(),
                            EntryTimeline::ACTION_UPDATED,
                            'Entry updated',
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
                    'Entry deleted',
                    ['title' => $entry->title]
                );
            }
        });
    }
}
