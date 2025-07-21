<?php

namespace Database\Factories;

use App\Models\Entry;
use App\Models\EntryDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EntryDocument>
 */
class EntryDocumentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EntryDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentTypes = array_keys(EntryDocument::DOCUMENT_TYPES);
        $extensions = ['pdf', 'jpg', 'png', 'doc', 'docx', 'txt'];
        $extension = $this->faker->randomElement($extensions);
        $fileName = $this->faker->uuid().'.'.$extension;

        return [
            'entry_id' => Entry::factory(),
            'original_name' => $this->faker->words(3, true).'.'.$extension,
            'file_name' => $fileName,
            'file_path' => 'entry-documents/'.$this->faker->uuid().'/'.$fileName,
            'mime_type' => $this->getMimeType($extension),
            'file_size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'document_type' => $this->faker->randomElement($documentTypes),
            'description' => $this->faker->optional(0.7)->sentence(),
        ];
    }

    /**
     * Get mime type based on extension.
     */
    private function getMimeType(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }

    /**
     * Indicate that the document is a medical request.
     */
    public function medicalRequest(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'medical_request',
            'description' => 'Solicitação médica para '.$this->faker->words(3, true),
        ]);
    }

    /**
     * Indicate that the document is an exam result.
     */
    public function examResult(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'exam_result',
            'description' => 'Resultado de exame de '.$this->faker->words(2, true),
        ]);
    }

    /**
     * Indicate that the document is a medical report.
     */
    public function medicalReport(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'medical_report',
            'description' => 'Relatório médico sobre '.$this->faker->words(3, true),
        ]);
    }

    /**
     * Indicate that the document is a prescription.
     */
    public function prescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => 'prescription',
            'description' => 'Receita médica para '.$this->faker->words(2, true),
        ]);
    }

    /**
     * Indicate that the document is a PDF.
     */
    public function pdf(): static
    {
        $fileName = $this->faker->uuid().'.pdf';

        return $this->state(fn (array $attributes) => [
            'original_name' => $this->faker->words(3, true).'.pdf',
            'file_name' => $fileName,
            'file_path' => 'entry-documents/'.$this->faker->uuid().'/'.$fileName,
            'mime_type' => 'application/pdf',
        ]);
    }

    /**
     * Indicate that the document is an image.
     */
    public function image(): static
    {
        $extension = $this->faker->randomElement(['jpg', 'png']);
        $fileName = $this->faker->uuid().'.'.$extension;

        return $this->state(fn (array $attributes) => [
            'original_name' => $this->faker->words(3, true).'.'.$extension,
            'file_name' => $fileName,
            'file_path' => 'entry-documents/'.$this->faker->uuid().'/'.$fileName,
            'mime_type' => $extension === 'jpg' ? 'image/jpeg' : 'image/png',
        ]);
    }

    /**
     * Indicate that the document has a large file size.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $this->faker->numberBetween(5242880, 10485760), // 5MB to 10MB
        ]);
    }

    /**
     * Indicate that the document has a small file size.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_size' => $this->faker->numberBetween(1024, 102400), // 1KB to 100KB
        ]);
    }
}
