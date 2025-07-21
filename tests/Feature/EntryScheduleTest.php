<?php

use App\Models\Entry;
use App\Models\EntryStatus;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user for authentication
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create necessary entry statuses if they don't exist
    EntryStatus::firstOrCreate(
        ['slug' => 'pending'],
        [
            'name' => 'Pendente',
            'color' => '#FFC107',
            'is_final' => false,
            'sort_order' => 1
        ]
    );

    EntryStatus::firstOrCreate(
        ['slug' => 'exam_scheduled'],
        [
            'name' => 'Exame Agendado',
            'color' => '#28A745',
            'is_final' => false,
            'sort_order' => 2
        ]
    );

    EntryStatus::firstOrCreate(
        ['slug' => 'exam_ready'],
        [
            'name' => 'Exame Pronto',
            'color' => '#007BFF',
            'is_final' => false,
            'sort_order' => 3
        ]
    );

    // Create a patient
    $this->patient = Patient::factory()->create([
        'created_by' => $this->user->id,
    ]);
});

test('can schedule exam for pending entry', function () {
    $pendingStatus = EntryStatus::where('slug', 'pending')->first();

    $entry = Entry::factory()->create([
        'patient_id' => $this->patient->id,
        'current_status_id' => $pendingStatus->id,
        'created_by' => $this->user->id,
    ]);

    $scheduledDate = now()->addDays(3)->format('Y-m-d H:i:s');

    $response = $this->putJson("/api/entries/{$entry->id}/schedule-exam", [
        'exam_scheduled_date' => $scheduledDate,
        'reason' => 'Agendamento inicial'
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Exame agendado com sucesso'
    ]);

    $entry->refresh();
    expect($entry->hasStatus('exam_scheduled'))->toBeTrue();
});

test('can reschedule exam for already scheduled entry', function () {
    $examScheduledStatus = EntryStatus::where('slug', 'exam_scheduled')->first();

    $entry = Entry::factory()->create([
        'patient_id' => $this->patient->id,
        'current_status_id' => $examScheduledStatus->id,
        'created_by' => $this->user->id,
    ]);

    $newScheduledDate = now()->addDays(5)->format('Y-m-d H:i:s');

    $response = $this->putJson("/api/entries/{$entry->id}/schedule-exam", [
        'exam_scheduled_date' => $newScheduledDate,
        'reason' => 'Reagendamento necessário'
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'Exame agendado com sucesso'
    ]);

    $entry->refresh();
    expect($entry->hasStatus('exam_scheduled'))->toBeTrue();
});

test('cannot schedule exam without authentication', function () {
    $this->post('/logout');

    $entry = Entry::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);

    $scheduledDate = now()->addDays(3)->format('Y-m-d H:i:s');

    $response = $this->putJson("/api/entries/{$entry->id}/schedule-exam", [
        'exam_scheduled_date' => $scheduledDate,
        'reason' => 'Test scheduling'
    ]);

    $response->assertStatus(401);
});

test('validates required fields for scheduling exam', function () {
    $entry = Entry::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->putJson("/api/entries/{$entry->id}/schedule-exam", []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['exam_scheduled_date']);
});

test('validates exam scheduled date is in the future', function () {
    $entry = Entry::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);

    $pastDate = now()->subDays(1)->format('Y-m-d H:i:s');

    $response = $this->putJson("/api/entries/{$entry->id}/schedule-exam", [
        'exam_scheduled_date' => $pastDate,
        'reason' => 'Test past date'
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['exam_scheduled_date']);
});

test('handles non-existent entry gracefully', function () {
    $scheduledDate = now()->addDays(3)->format('Y-m-d H:i:s');

    $response = $this->putJson("/api/entries/999999/schedule-exam", [
        'exam_scheduled_date' => $scheduledDate,
        'reason' => 'Test non-existent entry'
    ]);

    $response->assertStatus(404);
});
