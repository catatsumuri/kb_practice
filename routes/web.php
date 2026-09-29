<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentNamespaceController;
use App\Http\Controllers\OgpController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Public documents are viewable by guests, so these two are registered
// outside the auth group. Their numeric/alpha constraints keep them from
// colliding with the namespace and resource routes below regardless of
// registration order.
Route::get('documents/{document}', [DocumentController::class, 'show'])
    ->whereNumber('document')
    ->name('documents.show');

// Also public (rendered documents need working link-preview cards for
// guests too) and, like documents/{namespace}/create below, registered
// before the documents/{namespace} wildcard route so the literal "ogp"
// segment isn't swallowed as a namespace slug.
Route::get('documents/ogp', [OgpController::class, 'fetch'])
    ->middleware('throttle:60,1')
    ->name('documents.ogp');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::post('documents/fetch-source', [DocumentController::class, 'fetchSource'])->name('documents.fetch-source');
    Route::post('documents/{document}/translate', [DocumentController::class, 'translate'])
        ->whereNumber('document')
        ->name('documents.translate');
    Route::post('documents/{document}/refresh-source', [DocumentController::class, 'refreshSource'])
        ->whereNumber('document')
        ->name('documents.refresh-source');
    Route::post('documents/{document}/source-snapshots/{snapshot}', [DocumentController::class, 'adoptSourceSnapshot'])
        ->whereNumber('document')
        ->name('documents.source-snapshots.adopt');
    Route::post('documents/{document}/revisions/{revision}/restore', [DocumentController::class, 'restoreRevision'])
        ->whereNumber('document')
        ->name('documents.revisions.restore');

    // Creating a document is always namespace-scoped now, so these two
    // actions are pulled out of Route::resource() below and registered
    // against {namespace} (alpha-constrained) instead of {document}
    // (numeric). Registered before documents.show-by-path — a route with
    // the same "documents/{namespace}/<second segment>" shape but a
    // wildcard second segment — so the literal "create" segment can
    // never be swallowed by {path}. Same pattern as documents/create vs
    // documents/{namespace} below.
    Route::get('documents/{namespace}/create', [DocumentController::class, 'create'])
        ->where('namespace', '[a-z][a-z0-9-]*')
        ->name('documents.create');
    Route::post('documents/{namespace}', [DocumentController::class, 'store'])
        ->where('namespace', '[a-z][a-z0-9-]*')
        ->name('documents.store');

    Route::resource('documents', DocumentController::class)
        ->whereNumber('document')
        ->except(['create', 'store', 'show']);
    Route::resource('namespaces', DocumentNamespaceController::class)->only(['create', 'store', 'edit', 'update']);
});

// Public namespaces/documents are viewable by guests. Registered after the
// auth group's documents/{namespace}/create so that literal segment still
// wins over these wildcard shapes for the same "documents/{namespace}/…"
// prefix.
Route::get('documents/{namespace}', [DocumentNamespaceController::class, 'show'])
    ->where('namespace', '[a-z][a-z0-9-]*')
    ->name('namespaces.show');

Route::get('documents/{namespace}/{path}', [DocumentController::class, 'showByPath'])
    ->where('namespace', '[a-z][a-z0-9-]*')
    ->where('path', '.*')
    ->name('documents.show-by-path');

require __DIR__.'/settings.php';
