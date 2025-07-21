<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\EntryDocumentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/entries', [EntryController::class, 'index'])->name('entries.index');
    Route::get('/entries/active', [EntryController::class, 'active'])->name('entries.active');
    Route::get('/entries/scheduled', [EntryController::class, 'scheduled'])->name('entries.scheduled');
    Route::get('/entries/exam-ready', [EntryController::class, 'examReady'])->name('entries.exam-ready');
    Route::get('/entries/completed', [EntryController::class, 'completed'])->name('entries.completed');
    Route::get('/entries/cancelled', [EntryController::class, 'cancelled'])->name('entries.cancelled');
    Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');
    Route::get('/entries/{id}', [EntryController::class, 'show'])->name('entries.show');
    Route::get('/entries/{id}/print', [EntryController::class, 'print'])->name('entries.print');
    Route::delete('/entries/{id}', [EntryController::class, 'destroy'])->name('entries.destroy');

    // Status management routes
    Route::get('/entry-statuses', [EntryController::class, 'getStatuses'])->name('entries.statuses');
    Route::get('/entries/{id}/next-statuses', [EntryController::class, 'getNextStatuses'])->name('entries.next-statuses');
    Route::get('/entries/{id}/status-history', [EntryController::class, 'getStatusHistory'])->name('entries.status-history');
    Route::put('/entries/{id}/transition-status', [EntryController::class, 'transitionStatus'])->name('entries.transition-status');

    // Legacy status routes (backward compatibility)
    Route::put('/entries/{id}/complete', [EntryController::class, 'complete'])->name('entries.complete');
    Route::put('/entries/{id}/schedule-exam', [EntryController::class, 'scheduleExam'])->name('entries.schedule-exam');
    Route::put('/entries/{id}/mark-exam-ready', [EntryController::class, 'markExamReady'])->name('entries.mark-exam-ready');
    Route::put('/entries/{id}/cancel', [EntryController::class, 'cancel'])->name('entries.cancel');

    // Entry document routes
    Route::get('/entry-documents/types', [EntryDocumentController::class, 'getDocumentTypes'])->name('entry-documents.types');
    Route::post('/entries/{entryId}/documents', [EntryDocumentController::class, 'store'])->name('entry-documents.store');
    Route::get('/entries/{entryId}/documents', [EntryDocumentController::class, 'index'])->name('entry-documents.index');
    Route::get('/entries/{entryId}/documents/{documentId}', [EntryDocumentController::class, 'show'])->name('entry-documents.show');
    Route::get('/entries/{entryId}/documents/{documentId}/download', [EntryDocumentController::class, 'download'])->name('entry-documents.download');
    Route::delete('/entries/{entryId}/documents/{documentId}', [EntryDocumentController::class, 'destroy'])->name('entry-documents.destroy');
});
