<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $entry_id
 * @property string $original_name
 * @property string $file_name
 * @property string $file_path
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $document_type
 * @property string|null $description
 * @property int|null $uploaded_by
 */
class EntryDocument extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'entry_id',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'document_type',
        'description',
        'uploaded_by',
    ];

    /**
     * Validation rules for the model
     */
    public static function rules(): array
    {
        return [
            'entry_id' => 'required|exists:entries,id',
            'original_name' => 'required|string|max:255',
            'file_name' => 'required|string|max:255',
            'file_path' => 'required|string|max:500',
            'mime_type' => 'required|string|max:100',
            'file_size' => 'required|integer|min:1',
            'document_type' => 'nullable|string|in:'.implode(',', array_keys(self::DOCUMENT_TYPES)),
            'description' => 'nullable|string|max:500',
            'uploaded_by' => 'nullable|exists:users,id',
        ];
    }

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
     * Get the entry that owns the document.
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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

        return round($bytes, 2).' '.$units[$i];
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

        // Validate required fields when creating
        static::creating(function ($document) {
            if (empty($document->entry_id)) {
                throw new \InvalidArgumentException('entry_id is required and cannot be null');
            }
            if (empty($document->original_name)) {
                throw new \InvalidArgumentException('original_name is required and cannot be null');
            }
            if (empty($document->file_name)) {
                throw new \InvalidArgumentException('file_name is required and cannot be null');
            }
            if (empty($document->file_path)) {
                throw new \InvalidArgumentException('file_path is required and cannot be null');
            }
            if (empty($document->mime_type)) {
                throw new \InvalidArgumentException('mime_type is required and cannot be null');
            }
            if (empty($document->file_size) || $document->file_size <= 0) {
                throw new \InvalidArgumentException('file_size is required and must be greater than 0');
            }
        });

        // Validate required fields when updating
        static::updating(function ($document) {
            if (empty($document->entry_id)) {
                throw new \InvalidArgumentException('entry_id is required and cannot be null');
            }
            if (empty($document->original_name)) {
                throw new \InvalidArgumentException('original_name is required and cannot be null');
            }
            if (empty($document->file_name)) {
                throw new \InvalidArgumentException('file_name is required and cannot be null');
            }
            if (empty($document->file_path)) {
                throw new \InvalidArgumentException('file_path is required and cannot be null');
            }
            if (empty($document->mime_type)) {
                throw new \InvalidArgumentException('mime_type is required and cannot be null');
            }
            if (empty($document->file_size) || $document->file_size <= 0) {
                throw new \InvalidArgumentException('file_size is required and must be greater than 0');
            }
        });

        static::deleting(function ($document) {
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
        });
    }

    /**
     * Document type constants for entries.
     */
    const DOCUMENT_TYPES = [
        'medical_request' => 'Solicitação Médica',
        'exam_result' => 'Resultado de Exame',
        'medical_report' => 'Relatório Médico',
        'prescription' => 'Receita Médica',
        'authorization' => 'Autorização',
        'referral' => 'Encaminhamento',
        'lab_result' => 'Resultado Laboratorial',
        'imaging_result' => 'Resultado de Imagem',
        'consultation_note' => 'Nota de Consulta',
        'discharge_summary' => 'Resumo de Alta',
        'insurance_card' => 'Cartão do Convênio',
        'sus_card' => 'Cartão SUS',
        'identification' => 'Documento de Identidade',
        'medical_history' => 'Histórico Médico',
        'consent_form' => 'Termo de Consentimento',
        'pre_authorization' => 'Pré-Autorização',
        'treatment_plan' => 'Plano de Tratamento',
        'surgery_report' => 'Relatório Cirúrgico',
        'anesthesia_record' => 'Ficha Anestésica',
        'vital_signs' => 'Sinais Vitais',
        'medication_list' => 'Lista de Medicamentos',
        'allergy_record' => 'Registro de Alergias',
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
