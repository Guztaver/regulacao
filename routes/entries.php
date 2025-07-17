<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntryController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/entries', [EntryController::class, 'index'])->name('entries.index');
    Route::get('/entries/completed', [EntryController::class, 'completed'])->name('entries.completed');
    Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');
    Route::get('/entries/{id}', [EntryController::class, 'show'])->name('entries.show');
    Route::delete('/entries/{id}', [EntryController::class, 'destroy'])->name('entries.destroy');
    Route::put('/entries/{id}/complete', [EntryController::class, 'complete'])->name('entries.complete');
    Route::put('/entries/{id}/schedule-exam', [EntryController::class, 'scheduleExam'])->name('entries.schedule-exam');
    Route::put('/entries/{id}/mark-exam-ready', [EntryController::class, 'markExamReady'])->name('entries.mark-exam-ready');
});
