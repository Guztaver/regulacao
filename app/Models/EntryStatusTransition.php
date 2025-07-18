<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class EntryStatusTransition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'entry_id',
        'from_status_id',
        'to_status_id',
        'user_id',
        'reason',
        'metadata',
        'transitioned_at',
        'scheduled_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'transitioned_at' => 'datetime',
            'scheduled_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the entry that this transition belongs to.
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * Get the user who performed this transition.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the status this transition came from.
     */
    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(EntryStatus::class, 'from_status_id');
    }

    /**
     * Get the status this transition went to.
     */
    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(EntryStatus::class, 'to_status_id');
    }

    /**
     * Scope to get transitions for a specific entry.
     */
    public function scopeForEntry($query, string $entryId)
    {
        return $query->where('entry_id', $entryId);
    }

    /**
     * Scope to get transitions by a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get transitions to a specific status.
     */
    public function scopeToStatus($query, int $statusId)
    {
        return $query->where('to_status_id', $statusId);
    }

    /**
     * Scope to get transitions from a specific status.
     */
    public function scopeFromStatus($query, int $statusId)
    {
        return $query->where('from_status_id', $statusId);
    }

    /**
     * Scope to order by transition time (newest first).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('transitioned_at', 'desc');
    }

    /**
     * Scope to order by transition time (oldest first).
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('transitioned_at', 'asc');
    }

    /**
     * Create a new status transition.
     */
    public static function createTransition(
        string $entryId,
        ?int $fromStatusId,
        int $toStatusId,
        ?int $userId = null,
        ?string $reason = null,
        array $metadata = []
    ): self {
        $effectiveUserId = $userId ?? Auth::id() ?? 1; // Fallback to user ID 1 for system operations

        return self::create([
            'entry_id' => $entryId,
            'from_status_id' => $fromStatusId,
            'to_status_id' => $toStatusId,
            'user_id' => $effectiveUserId,
            'reason' => $reason,
            'metadata' => $metadata,
            'transitioned_at' => now(),
        ]);
    }

    /**
     * Get a human-readable description of this transition.
     */
    public function getDescriptionAttribute(): string
    {
        $fromStatusName = $this->fromStatus?->name ?? 'Initial';
        $toStatusName = $this->toStatus->name;

        return "Status changed from {$fromStatusName} to {$toStatusName}";
    }

    /**
     * Check if this transition represents an initial status assignment.
     */
    public function isInitialTransition(): bool
    {
        return $this->from_status_id === null;
    }

    /**
     * Get the scheduled date from metadata if this is an exam scheduling transition.
     */
    public function getScheduledDateAttribute(): ?string
    {
        if ($this->toStatus?->slug === EntryStatus::EXAM_SCHEDULED &&
            isset($this->metadata['scheduled_date'])) {
            return $this->metadata['scheduled_date'];
        }

        return null;
    }

    /**
     * Get additional notes from metadata.
     */
    public function getNotesAttribute(): ?string
    {
        return $this->metadata['notes'] ?? null;
    }

    /**
     * Boot the model and add model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure user_id is always set when creating
        static::creating(function ($transition) {
            if (empty($transition->user_id)) {
                $transition->user_id = Auth::id() ?? 1; // Fallback to user ID 1 for system operations
            }

            if (empty($transition->transitioned_at)) {
                $transition->transitioned_at = now();
            }
        });
    }
}
