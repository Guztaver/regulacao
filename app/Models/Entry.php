<?php

namespace App\Models;

use Database\Factories\EntryFactory;
use Faker\Core\Uuid;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

/**
 * @property Uuid $patient_id
 * @property string $title
 * @property bool $completed
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
        'completed'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public $timestamps = true;

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function toggleCompleted(): void
    {
        $this->completed = !$this->completed;
        $this->save();
    }
}
