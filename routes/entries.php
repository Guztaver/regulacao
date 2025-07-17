<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/entries', [EntryController::class, 'index'])->name('entries.index');
    Route::get('/entries/completed', [EntryController::class, 'completed'])->name('entries.completed');
    Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');
    Route::get('/entries/{id}', [EntryController::class, 'show'])->name('entries.show');
    Route::delete('/entries/{id}', [EntryController::class, 'destroy'])->name('entries.destroy');

    // Status management routes
    Route::get('/entry-statuses', [EntryController::class, 'getStatuses'])->name('entries.statuses');
    Route::get('/entries/{id}/next-statuses', [EntryController::class, 'getNextStatuses'])->name('entries.next-statuses');
    Route::put('/entries/{id}/transition-status', [EntryController::class, 'transitionStatus'])->name('entries.transition-status');

    // Legacy status routes (backward compatibility)
    Route::put('/entries/{id}/complete', [EntryController::class, 'complete'])->name('entries.complete');
    Route::put('/entries/{id}/schedule-exam', [EntryController::class, 'scheduleExam'])->name('entries.schedule-exam');
    Route::put('/entries/{id}/mark-exam-ready', [EntryController::class, 'markExamReady'])->name('entries.mark-exam-ready');
    Route::put('/entries/{id}/cancel', [EntryController::class, 'cancel'])->name('entries.cancel');
});
