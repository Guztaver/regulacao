<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Entry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthenticationBarrierTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test that unauthenticated users cannot access patient endpoints
     */
    public function test_unauthenticated_users_cannot_access_patient_endpoints(): void
    {
        // Test GET /api/patients
        $response = $this->getJson('/api/patients');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test POST /api/patients
        $response = $this->postJson('/api/patients', [
            'name' => 'Test Patient',
            'email' => 'test@example.com'
        ]);
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test GET /api/patients/{id}
        $response = $this->getJson('/api/patients/123');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test PUT /api/patients/{id}
        $response = $this->putJson('/api/patients/123', [
            'name' => 'Updated Patient',
            'email' => 'updated@example.com'
        ]);
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test DELETE /api/patients/{id}
        $response = $this->deleteJson('/api/patients/123');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Test that unauthenticated users cannot access entry endpoints
     */
    public function test_unauthenticated_users_cannot_access_entry_endpoints(): void
    {
        // Test GET /api/entries
        $response = $this->getJson('/api/entries');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test POST /api/entries
        $response = $this->postJson('/api/entries', [
            'patient_id' => '123',
            'title' => 'Test Entry'
        ]);
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test GET /api/entries/{id}
        $response = $this->getJson('/api/entries/123');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test DELETE /api/entries/{id}
        $response = $this->deleteJson('/api/entries/123');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test PUT /api/entries/{id}/complete
        $response = $this->putJson('/api/entries/123/complete');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test GET /api/entries/completed
        $response = $this->getJson('/api/entries/completed');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Test that unauthenticated users cannot access entry document endpoints
     */
    public function test_unauthenticated_users_cannot_access_document_endpoints(): void
    {
        // Test GET /api/entries/{entryId}/documents
        $response = $this->getJson('/api/entries/123/documents');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test POST /api/entries/{entryId}/documents
        $response = $this->postJson('/api/entries/123/documents', [
            'document_type' => 'medical_request',
            'description' => 'Test document'
        ]);
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test GET /api/entries/{entryId}/documents/{documentId}
        $response = $this->getJson('/api/entries/123/documents/456');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test DELETE /api/entries/{entryId}/documents/{documentId}
        $response = $this->deleteJson('/api/entries/123/documents/456');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test GET /api/entries/{entryId}/documents/{documentId}/download
        $response = $this->getJson('/api/entries/123/documents/456/download');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);

        // Test GET /api/entry-documents/types
        $response = $this->getJson('/api/entry-documents/types');
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * Test that authenticated users can access endpoints
     */
    public function test_authenticated_users_can_access_endpoints(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $this->actingAs($user);

        // Test GET /api/patients
        $response = $this->getJson('/api/patients');
        $response->assertStatus(200);

        // Test GET /api/entries
        $response = $this->getJson('/api/entries');
        $response->assertStatus(200);

        // Test GET /api/entry-documents/types
        $response = $this->getJson('/api/entry-documents/types');
        $response->assertStatus(200);
    }

    /**
     * Test that patient creation requires authentication and sets created_by
     */
    public function test_patient_creation_requires_authentication_and_sets_created_by(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $this->actingAs($user);

        $patientData = [
            'name' => 'João Silva',
            'phone' => '11999999999',
            'sus_number' => '123456789012345'
        ];

        $response = $this->postJson('/api/patients', $patientData);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Paciente criado com sucesso']);

        $this->assertDatabaseHas('patients', [
            'name' => 'João Silva',
            'created_by' => $user->id
        ]);

        $patient = Patient::where('name', 'João Silva')->first();
        $this->assertNotNull($patient->created_by);
        $this->assertEquals($user->id, $patient->created_by);
    }

    /**
     * Test that entry creation requires authentication and sets created_by
     */
    public function test_entry_creation_requires_authentication_and_sets_created_by(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $patient = Patient::factory()->create([
            'created_by' => $user->id
        ]);

        $this->actingAs($user);

        $entryData = [
            'patient_id' => $patient->id,
            'title' => 'Consulta de rotina'
        ];

        $response = $this->postJson('/api/entries', $entryData);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Entrada criada com sucesso']);

        $this->assertDatabaseHas('entries', [
            'patient_id' => $patient->id,
            'title' => 'Consulta de rotina',
            'created_by' => $user->id
        ]);

        $entry = Entry::where('title', 'Consulta de rotina')->first();
        $this->assertNotNull($entry->created_by);
        $this->assertEquals($user->id, $entry->created_by);
    }

    /**
     * Test that created_by cannot be null in database
     */
    public function test_created_by_cannot_be_null(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        // Test that trying to create a patient without created_by throws exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('created_by is required and cannot be null');

        Patient::create([
            'name' => 'Test Patient',
            'email' => 'test@example.com',
            'created_by' => null
        ]);
    }

    /**
     * Test validation rules for patient creation
     */
    public function test_patient_validation_rules(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $this->actingAs($user);

        // Test required fields
        $response = $this->postJson('/api/patients', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);

        // Test SUS number format
        $response = $this->postJson('/api/patients', [
            'name' => 'Test Patient',
            'sus_number' => 'invalid-sus'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sus_number']);

        // Test unique SUS number
        Patient::factory()->create([
            'sus_number' => '123456789012345',
            'created_by' => $user->id
        ]);

        $response = $this->postJson('/api/patients', [
            'name' => 'Another Patient',
            'sus_number' => '123456789012345'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sus_number']);
    }

    /**
     * Test validation rules for entry creation
     */
    public function test_entry_validation_rules(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $this->actingAs($user);

        // Test required fields
        $response = $this->postJson('/api/entries', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['patient_id', 'title']);

        // Test patient_id exists
        $response = $this->postJson('/api/entries', [
            'patient_id' => 'non-existent-id',
            'title' => 'Test Entry'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['patient_id']);
    }

    /**
     * Test that all critical authentication barriers are working
     */
    public function test_authentication_barriers_summary(): void
    {
        // Ensure that all our key endpoints require authentication
        $protectedEndpoints = [
            ['GET', '/api/patients'],
            ['POST', '/api/patients'],
            ['GET', '/api/entries'],
            ['POST', '/api/entries'],
            ['GET', '/api/entry-documents/types'],
        ];

        foreach ($protectedEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            $this->assertEquals(401, $response->getStatusCode(),
                "Endpoint {$method} {$endpoint} should require authentication");
        }

        // Test that with authentication, endpoints are accessible
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        foreach ($protectedEndpoints as [$method, $endpoint]) {
            if ($method === 'GET') {
                $response = $this->json($method, $endpoint);
                $this->assertNotEquals(401, $response->getStatusCode(),
                    "Authenticated user should access {$method} {$endpoint}");
            }
        }
    }
}
