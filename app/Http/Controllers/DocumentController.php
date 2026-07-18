<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Document::class);

        $documents = $request->user()->documents()
            ->select(['id', 'user_id', 'title', 'created_at'])
            ->with('user:id,name')
            ->latest()
            ->get();

        return Inertia::render('documents/index', [
            'documents' => $documents,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Document::class);

        return Inertia::render('documents/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Document::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $request->user()->documents()->create($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'ドキュメントを作成しました',
        ]);

        return to_route('documents.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document): Response
    {
        Gate::authorize('view', $document);

        $document->load('user:id,name');

        return Inertia::render('documents/show', [
            'document' => $document,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document): Response
    {
        Gate::authorize('update', $document);

        return Inertia::render('documents/edit', [
            'document' => $document,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $document->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'ドキュメントを更新しました',
        ]);

        return to_route('documents.show', $document);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'ドキュメントを削除しました',
        ]);

        return to_route('documents.index');
    }
}
