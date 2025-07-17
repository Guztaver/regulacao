<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntryController;

//Route::middleware(['auth', 'verified'])->group(function () {
Route::get('/entries', [EntryController::class, 'index'])->name('entries.index');
Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');
Route::get('/entries/{id}', [EntryController::class, 'show'])->name('entries.show');
Route::delete('/entries/{id}', [EntryController::class, 'destroy'])->name('entries.destroy');
Route::put('/entries/{id}/complete', [EntryController::class, 'complete'])->name('entries.complete');
//});
