<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// Sanctum CSRF route for SPA authentication
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])->middleware('web');

// Health check endpoint for Railway
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'laravel' => app()->version(),
    ]);
});

// Public patient lookup route (no auth required)
Route::post('/patient-lookup', [App\Http\Controllers\PatientController::class, 'lookupBySusNumber'])->name('patient.lookup');

Route::get('/', static fn () => Inertia::render('Welcome'))->name('home');

Route::get('dashboard', static fn () => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

Route::get('entries/active', static fn () => Inertia::render('ActiveEntries'))->middleware(['auth', 'verified'])->name('entries.active.page');
Route::get('entries/scheduled', static fn () => Inertia::render('ScheduledEntries'))->middleware(['auth', 'verified'])->name('entries.scheduled.page');
Route::get('entries/exam-ready', static fn () => Inertia::render('ExamReadyEntries'))->middleware(['auth', 'verified'])->name('entries.exam-ready.page');
Route::get('entries/completed', static fn () => Inertia::render('CompletedEntries'))->middleware(['auth', 'verified'])->name('entries.completed.page');
Route::get('entries/cancelled', static fn () => Inertia::render('CancelledEntries'))->middleware(['auth', 'verified'])->name('entries.cancelled.page');

Route::get('patients', static fn () => Inertia::render('PatientList'))->middleware(['auth', 'verified'])->name('patients.list.page');
Route::get('patients/{id}', static fn (string $id) => Inertia::render('PatientView', ['patientId' => $id]))->middleware(['auth', 'verified'])->name('patients.view.page');

Route::get('timeline-test', static fn () => Inertia::render('TimelineTest'))->middleware(['auth', 'verified'])->name('timeline.test');

// API routes for authenticated users - using web middleware for session auth
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    // Entry routes
    Route::get('/entries', [App\Http\Controllers\EntryController::class, 'index'])->name('api.entries.index');
    Route::get('/entries/active', [App\Http\Controllers\EntryController::class, 'active'])->name('api.entries.active');
    Route::get('/entries/scheduled', [App\Http\Controllers\EntryController::class, 'scheduled'])->name('api.entries.scheduled');
    Route::get('/entries/exam-ready', [App\Http\Controllers\EntryController::class, 'examReady'])->name('api.entries.exam-ready');
    Route::get('/entries/completed', [App\Http\Controllers\EntryController::class, 'completed'])->name('api.entries.completed');
    Route::get('/entries/cancelled', [App\Http\Controllers\EntryController::class, 'cancelled'])->name('api.entries.cancelled');
    Route::post('/entries', [App\Http\Controllers\EntryController::class, 'store'])->name('api.entries.store');
    Route::get('/entries/{id}', [App\Http\Controllers\EntryController::class, 'show'])->name('api.entries.show');
    Route::delete('/entries/{id}', [App\Http\Controllers\EntryController::class, 'destroy'])->name('api.entries.destroy');

    // Entry status routes
    Route::get('/entry-statuses', [App\Http\Controllers\EntryController::class, 'getStatuses'])->name('api.entries.statuses');
    Route::get('/entries/{id}/next-statuses', [App\Http\Controllers\EntryController::class, 'getNextStatuses'])->name('api.entries.next-statuses');
    Route::get('/entries/{id}/status-history', [App\Http\Controllers\EntryController::class, 'getStatusHistory'])->name('api.entries.status-history');
    Route::put('/entries/{id}/transition-status', [App\Http\Controllers\EntryController::class, 'transitionStatus'])->name('api.entries.transition-status');

    // Legacy entry routes
    Route::put('/entries/{id}/complete', [App\Http\Controllers\EntryController::class, 'complete'])->name('api.entries.complete');
    Route::put('/entries/{id}/schedule-exam', [App\Http\Controllers\EntryController::class, 'scheduleExam'])->name('api.entries.schedule-exam');
    Route::put('/entries/{id}/mark-exam-ready', [App\Http\Controllers\EntryController::class, 'markExamReady'])->name('api.entries.mark-exam-ready');
    Route::put('/entries/{id}/cancel', [App\Http\Controllers\EntryController::class, 'cancel'])->name('api.entries.cancel');

    // Patient routes
    Route::get('/patients', [App\Http\Controllers\PatientController::class, 'index'])->name('api.patients.index');
    Route::post('/patients', [App\Http\Controllers\PatientController::class, 'store'])->name('api.patients.store');
    Route::get('/patients/{id}', [App\Http\Controllers\PatientController::class, 'show'])->name('api.patients.show');
    Route::put('/patients/{id}', [App\Http\Controllers\PatientController::class, 'update'])->name('api.patients.update');
    Route::delete('/patients/{id}', [App\Http\Controllers\PatientController::class, 'destroy'])->name('api.patients.destroy');

    // Entry document routes
    Route::get('/entry-documents/types', [App\Http\Controllers\EntryDocumentController::class, 'getDocumentTypes'])->name('api.entry-documents.types');
    Route::post('/entries/{entryId}/documents', [App\Http\Controllers\EntryDocumentController::class, 'store'])->name('api.entry-documents.store');
    Route::get('/entries/{entryId}/documents', [App\Http\Controllers\EntryDocumentController::class, 'index'])->name('api.entry-documents.index');
    Route::get('/entries/{entryId}/documents/{documentId}', [App\Http\Controllers\EntryDocumentController::class, 'show'])->name('api.entry-documents.show');
    Route::get('/entries/{entryId}/documents/{documentId}/download', [App\Http\Controllers\EntryDocumentController::class, 'download'])->name('api.entry-documents.download');
    Route::delete('/entries/{entryId}/documents/{documentId}', [App\Http\Controllers\EntryDocumentController::class, 'destroy'])->name('api.entry-documents.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
