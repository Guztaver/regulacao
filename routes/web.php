<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EntryController;

Route::get('/', static fn () => Inertia::render('Welcome'))->name('home');

Route::get('dashboard', static fn () => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

Route::get('entries/completed', static fn () => Inertia::render('CompletedEntries'))->middleware(['auth', 'verified'])->name('entries.completed.page');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
