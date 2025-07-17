<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EntryController;

// Sanctum CSRF route for SPA authentication
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])->middleware('web');

Route::get('/', static fn () => Inertia::render('Welcome'))->name('home');

Route::get('dashboard', static fn () => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

Route::get('entries/completed', static fn () => Inertia::render('CompletedEntries'))->middleware(['auth', 'verified'])->name('entries.completed.page');

Route::get('patients', static fn () => Inertia::render('PatientList'))->middleware(['auth', 'verified'])->name('patients.list.page');
Route::get('patients/{id}', static fn (string $id) => Inertia::render('PatientView', ['patientId' => $id]))->middleware(['auth', 'verified'])->name('patients.view.page');

Route::get('timeline-test', static fn () => Inertia::render('TimelineTest'))->middleware(['auth', 'verified'])->name('timeline.test');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
