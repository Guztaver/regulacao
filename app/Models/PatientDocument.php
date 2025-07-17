<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $patient_id
 * @property string $original_name
 * @property string $file_name
 * @property string $file_path
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $document_type
 * @property string|null $description
 */
class PatientDocument extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'patient_id',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'document_type',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the patient that owns the document.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the full URL to the document.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get human readable file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the document is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the document is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Delete the document file when the model is deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            if (Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
        });
    }

    /**
     * Document type constants.
     */
    const DOCUMENT_TYPES = [
        'identity' => 'Documento de Identidade',
        'cpf' => 'CPF',
        'sus_card' => 'Cartão SUS',
        'medical_record' => 'Prontuário Médico',
        'exam_result' => 'Resultado de Exame',
        'prescription' => 'Receita Médica',
        'insurance' => 'Plano de Saúde',
        'other' => 'Outros',
    ];

    /**
     * Get the document type label.
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? 'Outros';
    }
}
