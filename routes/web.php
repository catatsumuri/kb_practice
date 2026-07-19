<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\OgpController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::resource('documents', DocumentController::class);
    Route::get('/api/ogp', [OgpController::class, 'fetch'])
        ->middleware('throttle:60,1')
        ->name('api.ogp');
});

require __DIR__.'/settings.php';
