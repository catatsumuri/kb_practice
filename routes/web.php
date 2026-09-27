<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentLikeController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('documents/{document}/shared', [DocumentController::class, 'shared'])
    ->middleware('signed')
    ->name('documents.shared');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('documents/fetch-source', [DocumentController::class, 'fetchSource'])->name('documents.fetch-source');
    Route::post('documents/{document}/translate', [DocumentController::class, 'translate'])->name('documents.translate');
    Route::resource('documents', DocumentController::class);
    Route::post('documents/{document}/likes', [DocumentLikeController::class, 'store'])->name('documents.likes.store');
    Route::delete('documents/{document}/likes', [DocumentLikeController::class, 'destroy'])->name('documents.likes.destroy');
});

require __DIR__.'/settings.php';
