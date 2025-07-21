<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use App\Models\User;
use Symfony\Component\Uid\Uuid;

/**
 * @property Uuid $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string|null $sus_number
 * @property int $created_by
 */
class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'sus_number',
        'created_by'
    ];

    /**
     * Validation rules for the model
     */
    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:patients,email',
            'phone' => 'nullable|string|max:20',
            'sus_number' => 'nullable|string|size:15|unique:patients,sus_number',
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

    public $timestamps = true;

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }



    /**
     * Boot the model and add model events
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure created_by is always set when creating
        static::creating(function ($patient) {
            if (empty($patient->created_by)) {
                throw new \InvalidArgumentException('created_by is required and cannot be null');
            }
        });

        // Prevent updates that would set created_by to null
        static::updating(function ($patient) {
            if (empty($patient->created_by)) {
                throw new \InvalidArgumentException('created_by is required and cannot be null');
            }
        });
    }
}
