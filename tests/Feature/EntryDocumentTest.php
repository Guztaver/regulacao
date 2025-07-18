<?php

namespace Tests\Feature;

use App\Models\Entry;
use App\Models\EntryDocument;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EntryDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Entry $entry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $patient = Patient::factory()->create(['created_by' => $this->user->id]);
        $this->entry = Entry::factory()->create([
            'patient_id' => $patient->id,
            'created_by' => $this->user->id,
        ]);

        Storage::fake('public');
    }

    public function test_can_upload_document_to_entry(): void
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 100);

        $response = $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->id}/documents", [
                'file' => $file,
                'document_type' => 'medical_request',
                'description' => 'Test medical request document',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'document' => [
                    'id',
                    'original_name',
                    'file_name',
                    'file_path',
                    'mime_type',
                    'file_size',
                    'document_type',
                    'description',
                    'entry',
                ],
            ]);

        $this->assertDatabaseHas('entry_documents', [
            'entry_id' => $this->entry->id,
            'original_name' => 'test-document.pdf',
            'document_type' => 'medical_request',
            'description' => 'Test medical request document',
        ]);

        // Verify file was stored
        $document = EntryDocument::where('entry_id', $this->entry->id)->first();
        Storage::assertExists('public/' . $document->file_path);
    }

    public function test_can_list_entry_documents(): void
    {
        EntryDocument::factory()->count(3)->create(['entry_id' => $this->entry->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/entries/{$this->entry->id}/documents");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'entry',
                'documents' => [
                    '*' => [
                        'id',
                        'original_name',
                        'file_name',
                        'mime_type',
                        'file_size',
                        'formatted_file_size',
                        'document_type',
                        'document_type_label',
                        'description',
                        'url',
                        'is_image',
                        'is_pdf',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('documents'));
    }

    public function test_can_show_specific_entry_document(): void
    {
        $document = EntryDocument::factory()->create(['entry_id' => $this->entry->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/entries/{$this->entry->id}/documents/{$document->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'document' => [
                    'id',
                    'original_name',
                    'file_name',
                    'mime_type',
                    'file_size',
                    'formatted_file_size',
                    'document_type',
                    'document_type_label',
                    'description',
                    'url',
                    'is_image',
                    'is_pdf',
                    'created_at',
                ],
                'entry',
            ]);
    }

    public function test_can_delete_entry_document(): void
    {
        $document = EntryDocument::factory()->create(['entry_id' => $this->entry->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/entries/{$this->entry->id}/documents/{$document->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Documento excluído com sucesso']);

        $this->assertDatabaseMissing('entry_documents', [
            'id' => $document->id,
        ]);
    }

    public function test_can_get_document_types(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/entry-documents/types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'document_types',
            ]);

        $documentTypes = $response->json('document_types');
        $this->assertIsArray($documentTypes);
        $this->assertArrayHasKey('medical_request', $documentTypes);
        $this->assertArrayHasKey('exam_result', $documentTypes);
        $this->assertArrayHasKey('medical_report', $documentTypes);
    }

    public function test_validates_required_fields_when_uploading(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->id}/documents", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file', 'document_type']);
    }

    public function test_validates_file_size_limit(): void
    {
        $file = UploadedFile::fake()->create('large-document.pdf', 11000); // 11MB

        $response = $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->id}/documents", [
                'file' => $file,
                'document_type' => 'medical_request',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_validates_document_type(): void
    {
        $file = UploadedFile::fake()->create('test-document.pdf', 100);

        $response = $this->actingAs($this->user)
            ->postJson("/api/entries/{$this->entry->id}/documents", [
                'file' => $file,
                'document_type' => 'invalid_type',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type']);
    }

    public function test_entry_has_documents_relationship(): void
    {
        $documents = EntryDocument::factory()->count(2)->create(['entry_id' => $this->entry->id]);

        $this->entry->refresh();
        $this->assertCount(2, $this->entry->documents);
        $this->assertInstanceOf(EntryDocument::class, $this->entry->documents->first());
    }

    public function test_entry_document_has_entry_relationship(): void
    {
        $document = EntryDocument::factory()->create(['entry_id' => $this->entry->id]);

        $this->assertInstanceOf(Entry::class, $document->entry);
        $this->assertEquals($this->entry->id, $document->entry->id);
    }

    public function test_entry_document_provides_formatted_file_size(): void
    {
        $document = EntryDocument::factory()->create([
            'entry_id' => $this->entry->id,
            'file_size' => 2048,
        ]);

        $this->assertEquals('2 KB', $document->formatted_file_size);
    }

    public function test_entry_document_detects_image_files(): void
    {
        $document = EntryDocument::factory()->create([
            'entry_id' => $this->entry->id,
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertTrue($document->isImage());
        $this->assertFalse($document->isPdf());
    }

    public function test_entry_document_detects_pdf_files(): void
    {
        $document = EntryDocument::factory()->create([
            'entry_id' => $this->entry->id,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertTrue($document->isPdf());
        $this->assertFalse($document->isImage());
    }

    public function test_requires_authentication_for_all_endpoints(): void
    {
        $document = EntryDocument::factory()->create(['entry_id' => $this->entry->id]);

        // Upload document
        $response = $this->postJson("/api/entries/{$this->entry->id}/documents", []);
        $response->assertStatus(401);

        // List documents
        $response = $this->getJson("/api/entries/{$this->entry->id}/documents");
        $response->assertStatus(401);

        // Show document
        $response = $this->getJson("/api/entries/{$this->entry->id}/documents/{$document->id}");
        $response->assertStatus(401);

        // Delete document
        $response = $this->deleteJson("/api/entries/{$this->entry->id}/documents/{$document->id}");
        $response->assertStatus(401);

        // Get document types
        $response = $this->getJson('/api/entry-documents/types');
        $response->assertStatus(401);
    }

    public function test_entry_controller_includes_document_count(): void
    {
        EntryDocument::factory()->count(3)->create(['entry_id' => $this->entry->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/entries/{$this->entry->id}");

        $response->assertStatus(200)
            ->assertJsonPath('entry.documents_count', 3);
    }

    public function test_entry_document_file_is_deleted_when_model_is_deleted(): void
    {
        $filePath = 'test-documents/test-document.pdf';

        // Create a fake file
        Storage::disk('public')->put($filePath, 'fake file content');

        $document = EntryDocument::factory()->create([
            'entry_id' => $this->entry->id,
            'file_path' => $filePath,
        ]);

        Storage::assertExists('public/' . $document->file_path);

        $document->delete();

        Storage::assertMissing('public/' . $document->file_path);
    }
}
