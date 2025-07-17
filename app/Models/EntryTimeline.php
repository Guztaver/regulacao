<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryTimeline extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'entry_id',
        'user_id',
        'action',
        'description',
        'metadata',
        'performed_at',
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
            'performed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the entry that this timeline belongs to.
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * Get the user who performed this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a timeline entry for an action
     */
    public static function logAction(
        string $entryId,
        int $userId,
        string $action,
        string $description,
        array $metadata = []
    ): self {
        return self::create([
            'entry_id' => $entryId,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'performed_at' => now(),
        ]);
    }

    /**
     * Timeline action constants
     */
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_COMPLETED = 'completed';
    public const ACTION_UNCOMPLETED = 'uncompleted';
    public const ACTION_EXAM_SCHEDULED = 'exam_scheduled';
    public const ACTION_EXAM_READY = 'exam_ready';
    public const ACTION_DELETED = 'deleted';
}
