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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
