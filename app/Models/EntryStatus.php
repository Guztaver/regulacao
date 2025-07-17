<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    /**
     * Get the default pending status.
     */
    public static function getDefaultStatus(): self
    {
        return static::findBySlug('pending') ?? static::active()->ordered()->first();
    }

    /**
     * Check if this status can transition to another status.
     */
    public function canTransitionTo(EntryStatus $toStatus): bool
    {
        // If current status is final, no transitions allowed
        if ($this->is_final) {
            return false;
        }

        // Define allowed transitions
        $allowedTransitions = [
            'pending' => ['exam_scheduled', 'completed', 'cancelled'],
            'exam_scheduled' => ['exam_ready', 'completed', 'cancelled'],
            'exam_ready' => ['completed', 'cancelled'],
        ];

        return in_array($toStatus->slug, $allowedTransitions[$this->slug] ?? []);
    }

    /**
     * Get the next possible statuses from this status.
     */
    public function getNextStatuses(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->is_final) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        $allowedTransitions = [
            'pending' => ['exam_scheduled', 'completed', 'cancelled'],
            'exam_scheduled' => ['exam_ready', 'completed', 'cancelled'],
            'exam_ready' => ['completed', 'cancelled'],
        ];

        $allowedSlugs = $allowedTransitions[$this->slug] ?? [];

        return static::whereIn('slug', $allowedSlugs)->active()->ordered()->get();
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
