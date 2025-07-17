<?php
use Illuminate\Support\Facades\Route;

Route::get('/health', static fn () => response()->json(['status' => 'ok'], 200))->name('api.health');

require __DIR__.'/entries.php';
require __DIR__.'/patients.php';
