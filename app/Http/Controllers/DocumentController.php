<?php

namespace App\Http\Controllers;

use App\Actions\CheckDocumentSource;
use App\Actions\FetchSourceContent;
use App\Actions\TranslateDocument;
use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Enums\SourceCheckStatus;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\DocumentRevision;
use App\Models\DocumentSourceSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
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

        $namespaces = $request->user()->documentNamespaces()
            ->select(['id', 'owner_user_id', 'slug', 'name', 'source_url', 'is_public', 'created_at'])
            ->withCount('documents')
            ->latest()
            ->get();

        return Inertia::render('documents/index', [
            'namespaces' => $namespaces,
        ]);
    }

    /**
     * Show the form for creating a new resource. A `?path=` query
     * parameter pre-fills the slug field — followed from a red link for a
     * path that doesn't have a document yet.
     */
    public function create(Request $request, DocumentNamespace $namespace): Response
    {
        Gate::authorize('create', Document::class);
        Gate::authorize('view', $namespace);

        return Inertia::render('documents/create', [
            'namespace' => $namespace,
            'path' => $request->query('path'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, DocumentNamespace $namespace): RedirectResponse
    {
        Gate::authorize('create', Document::class);
        Gate::authorize('view', $namespace);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'visibility' => ['required', Rule::enum(DocumentVisibility::class)],
            'path' => [
                'nullable',
                'string',
                'max:255',
                // One or more slug segments joined by "/", e.g.
                // "concepts/system-one" — showByPath's route already
                // accepts a multi-segment {path} (see routes/web.php), so
                // a document can mirror a nested page from the site it
                // was translated from instead of being flattened into a
                // single segment.
                'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*(?:\/[a-z][a-z0-9]*(?:-[a-z0-9]+)*)*$/',
                Rule::notIn(config('document-namespaces.reserved_paths')),
                Rule::unique('documents', 'path')->where('document_namespace_id', $namespace->id),
            ],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:2048'],
            'source_title' => ['nullable', 'string', 'max:255'],
            'source_author' => ['nullable', 'string', 'max:255'],
            'source_content' => ['nullable', 'string'],
        ], [
            'path.regex' => 'スラッグは半角英数字とハイフンのみ使用できます（/ で区切って複数階層にできます）。',
            'path.not_in' => 'このスラッグは予約されているため使用できません。',
            'path.unique' => 'このスラッグは既に使用されています。',
        ]);

        $validated['document_type'] = filled($validated['source_url'] ?? null)
            ? DocumentType::Translation
            : DocumentType::Original;
        $validated['document_namespace_id'] = $namespace->id;

        $document = $request->user()->documents()->create($validated);

        if (filled($document->source_content)) {
            $document->recordSourceSnapshot($document->source_content, $document->source_title, adopt: true);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'ドキュメントを作成しました',
        ]);

        return to_route('namespaces.show', $namespace);
    }

    /**
     * Fetch the content at a source URL so it can be used as the starting
     * point for a translation, without leaving the create form.
     */
    public function fetchSource(Request $request, FetchSourceContent $fetchSourceContent): Response
    {
        Gate::authorize('create', Document::class);

        $validated = $request->validate([
            'source_url' => ['required', 'url:http,https', 'max:2048'],
        ]);

        $fetched = $fetchSourceContent($validated['source_url']);

        return Inertia::render('documents/create', [
            'fetchedSource' => [
                'source_url' => $validated['source_url'],
                'source_title' => $fetched['title'],
                'content' => $fetched['content'],
            ],
        ]);
    }

    /**
     * Re-fetch the document's source URL and, if the content has changed
     * since the last adopted snapshot, record a new (not-yet-adopted)
     * snapshot for the edit page to offer as a diff.
     */
    public function refreshSource(Document $document, CheckDocumentSource $checkDocumentSource): RedirectResponse
    {
        Gate::authorize('update', $document);

        abort_if(blank($document->source_url), 422);

        $result = $checkDocumentSource($document);

        Inertia::flash('toast', match ($result->status) {
            SourceCheckStatus::Failed => ['type' => 'error', 'message' => $result->message],
            SourceCheckStatus::Updated => ['type' => 'success', 'message' => '原文の新しいバージョンを取得しました。差分を確認してください。'],
            default => ['type' => 'success', 'message' => '原文に変更はありませんでした'],
        });

        return to_route('documents.edit', $document);
    }

    /**
     * Adopt a previously fetched source snapshot, overwriting the
     * document's source_content/source_title with it.
     */
    public function adoptSourceSnapshot(Document $document, DocumentSourceSnapshot $snapshot): RedirectResponse
    {
        Gate::authorize('update', $document);

        abort_unless($snapshot->document_id === $document->id, 404);

        $document->update([
            'source_content' => $snapshot->content,
            'source_title' => $snapshot->title ?? $document->source_title,
            'document_source_snapshot_id' => $snapshot->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => '新しい原文を取り込みました',
        ]);

        return to_route('documents.edit', $document);
    }

    /**
     * Display the specified resource. Public documents are viewable by
     * guests; private documents require the owner to be authenticated.
     * A document in a namespace is redirected to its slug/path URL so that
     * URL is the only canonical address.
     */
    public function show(Request $request, Document $document): Response|RedirectResponse
    {
        Gate::authorize('view', $document);

        if ($document->document_namespace_id !== null && filled($document->path)) {
            return redirect()->route('documents.show-by-path', [
                'namespace' => $document->namespace,
                'path' => $document->path,
            ]);
        }

        return $this->renderShow($request, $document);
    }

    /**
     * Render the document page. Authorization is the caller's job.
     */
    private function renderShow(Request $request, Document $document): Response
    {
        return Inertia::render('documents/show', [
            ...$this->forDisplay($request, $document),
            'can' => [
                'update' => Gate::allows('update', $document),
                'delete' => Gate::allows('delete', $document),
            ],
        ]);
    }

    /**
     * Display the specified resource, addressed by its namespace's slug and
     * its own path rather than its numeric id
     * (e.g. /documents/typesafe/introduction). Shares the page/props with
     * show(), after the same authorization.
     */
    public function showByPath(Request $request, DocumentNamespace $namespace, string $path): Response
    {
        $document = $namespace->documents()->where('path', $path)->firstOrFail();

        Gate::authorize('view', $document);

        return $this->renderShow($request, $document);
    }

    /**
     * Load the document's author and namespace, plus the sibling documents
     * in the same namespace for the sidebar navigation, shared by the show
     * view. The visibility rule mirrors DocumentNamespaceController::show:
     * the namespace's owner sees all of their own documents, everyone else
     * only sees the namespace's public ones.
     *
     * @return array{document: Document, namespaceDocuments: Collection<int, Document>, namespaceNavigation: list<array{title: ?string, document: ?Document, label: ?string, children: array}>}
     */
    private function forDisplay(Request $request, Document $document): array
    {
        $document->load(['user:id,name', 'namespace:id,slug,name,owner_user_id,navigation']);

        $isNamespaceOwner = $document->namespace
            && $request->user()
            && $request->user()->id === $document->namespace->owner_user_id;

        $namespaceDocuments = $document->namespace
            ? $document->namespace->documents()
                ->when(
                    $isNamespaceOwner,
                    fn ($query) => $query->where('user_id', $request->user()->id),
                    fn ($query) => $query->where('visibility', DocumentVisibility::Public),
                )
                ->select(['id', 'title', 'path'])
                ->orderBy('title')
                ->get()
            : collect();

        return [
            'document' => $document,
            'namespaceDocuments' => $namespaceDocuments,
            'namespaceNavigation' => $document->namespace
                ? $document->namespace->navigationTree($namespaceDocuments)
                : [],
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document): Response
    {
        Gate::authorize('update', $document);

        $document->load(['adoptedSourceSnapshot', 'namespace:id,slug']);

        $latestSnapshot = $document->sourceSnapshots()->latest('fetched_at')->first();
        $pendingSnapshot = $latestSnapshot && $latestSnapshot->id !== $document->document_source_snapshot_id
            ? $latestSnapshot
            : null;

        $revisions = $document->revisions()
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('documents/edit', [
            'document' => $document,
            'pendingSnapshot' => $pendingSnapshot,
            'revisions' => $revisions,
        ]);
    }

    /**
     * Overwrite the document's content with an AI translation of its source.
     */
    public function translate(Request $request, Document $document, TranslateDocument $translateDocument): RedirectResponse
    {
        Gate::authorize('update', $document);

        abort_if(blank($document->source_content), 422);

        try {
            $translateDocument($document, $request->user()->id);
        } catch (\Throwable) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => '翻訳に失敗しました。しばらくしてからもう一度お試しください。',
            ]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => '原文をもとに本文を翻訳しました',
        ]);

        return to_route('documents.edit', $document);
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

        if ($document->title !== $validated['title'] || $document->content !== $validated['content']) {
            $this->recordRevision($document, $request->user()->id);
        }

        $document->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'ドキュメントを更新しました',
        ]);

        return $this->redirectToShow($document);
    }

    /**
     * Restore a past revision, overwriting the document's current
     * title/content with it. The current title/content are recorded as a
     * revision first, so restoring is itself reversible.
     */
    public function restoreRevision(Request $request, Document $document, DocumentRevision $revision): RedirectResponse
    {
        Gate::authorize('update', $document);

        abort_unless($revision->document_id === $document->id, 404);

        $this->recordRevision($document, $request->user()->id);

        $document->update([
            'title' => $revision->title,
            'content' => $revision->content,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => '過去の版を復元しました',
        ]);

        return to_route('documents.edit', $document);
    }

    /**
     * Redirect to the document's canonical page.
     */
    private function redirectToShow(Document $document): RedirectResponse
    {
        return $document->document_namespace_id !== null && filled($document->path)
            ? to_route('documents.show-by-path', ['namespace' => $document->namespace, 'path' => $document->path])
            : to_route('documents.show', $document);
    }

    /**
     * Snapshot the document's current title/content into its revision
     * history, before it gets overwritten.
     */
    private function recordRevision(Document $document, int $userId): DocumentRevision
    {
        return $document->revisions()->create([
            'user_id' => $userId,
            'title' => $document->title,
            'content' => $document->content,
        ]);
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
