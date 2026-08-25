<?php

namespace App\Http\Controllers;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
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
            ->select(['id', 'user_id', 'title', 'visibility', 'created_at'])
            ->withCount('likes')
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
            'visibility' => ['required', Rule::enum(DocumentVisibility::class)],
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
    public function show(Request $request, Document $document): Response
    {
        Gate::authorize('view', $document);

        return Inertia::render('documents/show', [
            ...$this->forDisplay($document),
            'liked' => $document->likes()->where('user_id', $request->user()->id)->exists(),
            'can' => [
                'update' => Gate::allows('update', $document),
                'delete' => Gate::allows('delete', $document),
            ],
            'shareUrl' => $document->visibility === DocumentVisibility::Unlisted
                ? URL::signedRoute('documents.shared', ['document' => $document])
                : null,
        ]);
    }

    /**
     * Display a document via its permanent signed share link, without requiring authentication.
     */
    public function shared(Document $document): Response
    {
        abort_unless($document->visibility === DocumentVisibility::Unlisted, 404);

        return Inertia::render('documents/shared', $this->forDisplay($document));
    }

    /**
     * Load the document's author and like count shared by the show and shared views.
     *
     * @return array{document: Document, likesCount: int}
     */
    private function forDisplay(Document $document): array
    {
        $document->load('user:id,name');

        return [
            'document' => $document,
            'likesCount' => $document->likes()->count(),
        ];
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
            'visibility' => ['required', Rule::enum(DocumentVisibility::class)],
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
