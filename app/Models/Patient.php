<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * @property Uuid $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string|null $sus_number
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
        'sus_number'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    public $timestamps = true;

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * Get the documents for the patient.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }
}
