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
 * @property bool $completed
 * @property int $created_by
 * @property bool $exam_ready
 * @property bool $exam_scheduled
 * @property bool $exam_scheduled_date
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
        'completed',
        'created_by',
        'exam_scheduled',
        'exam_scheduled_date',
        'exam_ready'
    ];

    /**
     * Validation rules for the model
     */
    public static function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'title' => 'required|string|max:255',
            'completed' => 'boolean',
            'created_by' => 'required|exists:users,id',
            'exam_scheduled' => 'boolean',
            'exam_scheduled_date' => 'nullable|date',
            'exam_ready' => 'boolean',
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
            'completed' => 'boolean',
            'exam_scheduled' => 'boolean',
            'exam_scheduled_date' => 'datetime',
            'exam_ready' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public $timestamps = true;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(EntryTimeline::class)->orderBy('performed_at', 'desc');
    }

    public function toggleCompleted(): void
    {
        $this->completed = !$this->completed;
    }

    public function scheduleExam(string $scheduledDate): void
    {
        $this->exam_scheduled = true;
        $this->exam_scheduled_date = $scheduledDate;
    }

    public function markExamReady(): void
    {
        $this->exam_ready = true;
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
        });

        // Log entry creation
        static::created(function ($entry) {
            if (Auth::check()) {
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
        });

        // Log entry updates
        static::updated(function ($entry) {
            if (Auth::check()) {
                $changes = $entry->getChanges();

                // Skip logging if only timestamps changed
                unset($changes['updated_at']);

                if (!empty($changes)) {
                    // Check for specific status changes and log them
                    if (array_key_exists('completed', $changes)) {
                        $action = $changes['completed'] ? EntryTimeline::ACTION_COMPLETED : EntryTimeline::ACTION_UNCOMPLETED;
                        $description = $changes['completed'] ? 'Entry marked as completed' : 'Entry marked as uncompleted';

                        EntryTimeline::logAction(
                            $entry->id,
                            Auth::id(),
                            $action,
                            $description
                        );
                    }

                    if (array_key_exists('exam_scheduled', $changes) && $changes['exam_scheduled']) {
                        EntryTimeline::logAction(
                            $entry->id,
                            Auth::id(),
                            EntryTimeline::ACTION_EXAM_SCHEDULED,
                            'Exam scheduled',
                            ['scheduled_date' => $entry->exam_scheduled_date]
                        );
                    }

                    if (array_key_exists('exam_ready', $changes) && $changes['exam_ready']) {
                        EntryTimeline::logAction(
                            $entry->id,
                            Auth::id(),
                            EntryTimeline::ACTION_EXAM_READY,
                            'Exam marked as ready'
                        );
                    }

                    // Log other field changes
                    $statusFields = ['completed', 'exam_scheduled', 'exam_scheduled_date', 'exam_ready'];
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
