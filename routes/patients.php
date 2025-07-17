<?php

use App\Http\Controllers\PatientController;

//Route::middleware(['auth', 'verified'])->group(function () {
Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
Route::get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');
Route::delete('/patients/{id}', [PatientController::class, 'destroy'])->name('patients.destroy');
//});
