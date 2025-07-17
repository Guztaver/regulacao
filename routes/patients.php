<?php

use App\Http\Controllers\PatientController;

use App\Http\Controllers\PatientDocumentController;

//Route::middleware(['auth', 'verified'])->group(function () {
Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
Route::get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');
Route::put('/patients/{id}', [PatientController::class, 'update'])->name('patients.update');
Route::delete('/patients/{id}', [PatientController::class, 'destroy'])->name('patients.destroy');

// Patient Documents
Route::get('/patients/{patientId}/documents', [PatientDocumentController::class, 'index'])->name('patients.documents.index');
Route::post('/patients/{patientId}/documents', [PatientDocumentController::class, 'store'])->name('patients.documents.store');
Route::get('/patients/{patientId}/documents/{documentId}', [PatientDocumentController::class, 'show'])->name('patients.documents.show');
Route::get('/patients/{patientId}/documents/{documentId}/download', [PatientDocumentController::class, 'download'])->name('patients.documents.download');
Route::delete('/patients/{patientId}/documents/{documentId}', [PatientDocumentController::class, 'destroy'])->name('patients.documents.destroy');

// Document Types
Route::get('/document-types', [PatientDocumentController::class, 'getDocumentTypes'])->name('document-types');
//});
