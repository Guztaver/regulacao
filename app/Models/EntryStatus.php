<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property string|null $description
 * @property bool $is_final
 * @property bool $is_active
 * @property int $sort_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Entry[] $entries
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EntryStatusTransition[] $transitionsTo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EntryStatusTransition[] $transitionsFrom
 */
class EntryStatus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'is_final',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_final' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the entries that have this status.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'current_status_id');
    }

    /**
     * Get the status transitions to this status.
     */
    public function transitionsTo(): HasMany
    {
        return $this->hasMany(EntryStatusTransition::class, 'to_status_id');
    }

    /**
     * Get the status transitions from this status.
     */
    public function transitionsFrom(): HasMany
    {
        return $this->hasMany(EntryStatusTransition::class, 'from_status_id');
    }

    /**
     * Scope to get only active statuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get statuses ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope to get final statuses.
     */
    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    /**
     * Scope to get non-final statuses.
     */
    public function scopeNonFinal($query)
    {
        return $query->where('is_final', false);
    }

    /**
     * Get status by slug.
     *
     * @return static|null
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get status by slug or throw exception.
     *
     * @return static
     *
     * @throws \RuntimeException
     */
    public static function findBySlugOrFail(string $slug): self
    {
        $status = static::findBySlug($slug);

        if (! $status) {
            throw new \RuntimeException("EntryStatus with slug '{$slug}' not found.");
        }

        return $status;
    }

    /**
     * Get the default pending status.
     *
     * @return static
     *
     * @throws \RuntimeException
     */
    public static function getDefaultStatus(): self
    {
        $defaultStatus = static::findBySlug('pending') ?? static::active()->ordered()->first();

        if (! $defaultStatus) {
            throw new \RuntimeException('No default status found. Please ensure at least one active EntryStatus exists.');
        }

        return $defaultStatus;
    }

    /**
     * Check if this status can transition to another status.
     */
    public function canTransitionTo(EntryStatus $toStatus): bool
    {
        // Define allowed transitions
        $allowedTransitions = [
            'pending' => ['exam_scheduled', 'completed', 'cancelled'],
            'exam_scheduled' => ['exam_scheduled', 'exam_ready', 'completed', 'cancelled'],
            'exam_ready' => ['completed', 'cancelled'],
            // Allow transitions from final statuses back to active states
            'completed' => ['pending', 'exam_scheduled', 'exam_ready', 'cancelled'],
            'cancelled' => ['pending', 'exam_scheduled', 'exam_ready', 'completed'],
        ];

        $canTransition = in_array($toStatus->slug, $allowedTransitions[$this->slug] ?? []);

        // Only log when transition is not allowed for debugging
        if (! $canTransition) {
            Log::warning('Status transition not allowed', [
                'from_status' => $this->slug,
                'to_status' => $toStatus->slug,
                'allowed_transitions' => $allowedTransitions[$this->slug] ?? [],
            ]);
        }

        return $canTransition;
    }

    /**
     * Get the next possible statuses from this status.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public function getNextStatuses(): \Illuminate\Database\Eloquent\Collection
    {
        $allowedTransitions = [
            'pending' => ['exam_scheduled', 'completed', 'cancelled'],
            'exam_scheduled' => ['exam_scheduled', 'exam_ready', 'completed', 'cancelled'],
            'exam_ready' => ['completed', 'cancelled'],
            // Allow transitions from final statuses back to active states
            'completed' => ['pending', 'exam_scheduled', 'exam_ready', 'cancelled'],
            'cancelled' => ['pending', 'exam_scheduled', 'exam_ready', 'completed'],
        ];

        $allowedSlugs = $allowedTransitions[$this->slug] ?? [];
        $nextStatuses = static::whereIn('slug', $allowedSlugs)->active()->ordered()->get();

        // Only log when no next statuses are found for debugging
        if ($nextStatuses->isEmpty()) {
            Log::debug('No next statuses available', [
                'current_status' => $this->slug,
                'current_status_is_final' => $this->is_final,
                'allowed_slugs' => $allowedSlugs,
            ]);
        }

        return $nextStatuses;
    }

    /**
     * Status constants for easy reference.
     */
    public const PENDING = 'pending';

    public const EXAM_SCHEDULED = 'exam_scheduled';

    public const EXAM_READY = 'exam_ready';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';
}
