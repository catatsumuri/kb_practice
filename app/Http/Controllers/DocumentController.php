<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TranslatorAgent;
use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\DocumentRevision;
use App\Models\DocumentSourceSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        $documents = $request->user()->documents()
            ->whereNull('document_namespace_id')
            ->select(['id', 'user_id', 'title', 'visibility', 'created_at'])
            ->with('user:id,name')
            ->latest()
            ->get();

        return Inertia::render('documents/index', [
            'namespaces' => $namespaces,
            'documents' => $documents,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(DocumentNamespace $namespace): Response
    {
        Gate::authorize('create', Document::class);
        Gate::authorize('view', $namespace);

        return Inertia::render('documents/create', [
            'namespace' => $namespace,
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
                'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/',
                Rule::notIn(config('document-namespaces.reserved_paths')),
                Rule::unique('documents', 'path')->where('document_namespace_id', $namespace->id),
            ],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'source_title' => ['nullable', 'string', 'max:255'],
            'source_author' => ['nullable', 'string', 'max:255'],
            'source_content' => ['nullable', 'string'],
        ], [
            'path.regex' => 'スラッグは半角英数字とハイフンのみ使用できます。',
            'path.not_in' => 'このスラッグは予約されているため使用できません。',
            'path.unique' => 'このスラッグは既に使用されています。',
        ]);

        $validated['document_type'] = filled($validated['source_url'] ?? null)
            ? DocumentType::Translation
            : DocumentType::Original;
        $validated['document_namespace_id'] = $namespace->id;

        $document = $request->user()->documents()->create($validated);

        if (filled($document->source_content)) {
            $this->recordSourceSnapshot($document, $document->source_content, $document->source_title, adopt: true);
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
    public function fetchSource(Request $request): Response
    {
        Gate::authorize('create', Document::class);

        $validated = $request->validate([
            'source_url' => ['required', 'url:http,https', 'max:2048'],
        ]);

        $fetched = $this->fetchAndParseSource($validated['source_url']);

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
    public function refreshSource(Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        abort_if(blank($document->source_url), 422);

        try {
            $fetched = $this->fetchAndParseSource($document->source_url);
        } catch (ValidationException $exception) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $exception->errors()['source_url'][0] ?? '原文の取得に失敗しました。',
            ]);

            return to_route('documents.edit', $document);
        }

        $hash = hash('sha256', $fetched['content']);

        if ($hash === $document->adoptedSourceSnapshot?->content_hash) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => '原文に変更はありませんでした',
            ]);

            return to_route('documents.edit', $document);
        }

        $this->recordSourceSnapshot($document, $fetched['content'], $fetched['title']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => '原文の新しいバージョンを取得しました。差分を確認してください。',
        ]);

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
     * Fetch a source URL's content and pull out a title from its first
     * Markdown h1, shared by the initial fetch (fetchSource) and later
     * freshness checks (refreshSource).
     *
     * @return array{content: string, title: ?string}
     */
    private function fetchAndParseSource(string $url): array
    {
        $this->assertUrlIsFetchable($url);

        try {
            $content = Http::timeout(10)->get($url)->throw()->body();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'source_url' => '指定のURLから本文を取得できませんでした。',
            ]);
        }

        $title = null;

        if (preg_match('/^#\s+(.+)$/m', $content, $matches) === 1) {
            $title = trim($matches[1]);
        }

        return ['content' => $content, 'title' => $title];
    }

    /**
     * Record a new source snapshot for a document, optionally adopting it
     * immediately (i.e. making it the version backing source_content).
     */
    private function recordSourceSnapshot(Document $document, string $content, ?string $title, bool $adopt = false): DocumentSourceSnapshot
    {
        $snapshot = $document->sourceSnapshots()->create([
            'content' => $content,
            'content_hash' => hash('sha256', $content),
            'title' => $title,
            'fetched_at' => now(),
        ]);

        if ($adopt) {
            $document->update(['document_source_snapshot_id' => $snapshot->id]);
        }

        return $snapshot;
    }

    /**
     * Reject hosts that resolve to private, loopback, or otherwise reserved
     * IP ranges, so this can't be used to probe the server's internal network.
     */
    private function assertUrlIsFetchable(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        $ip = $host !== null && filter_var($host, FILTER_VALIDATE_IP)
            ? $host
            : gethostbyname((string) $host);

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw ValidationException::withMessages([
                'source_url' => 'このURLからは取得できません。',
            ]);
        }
    }

    /**
     * Display the specified resource. Public documents are viewable by
     * guests; private documents require the owner to be authenticated.
     */
    public function show(Request $request, Document $document): Response
    {
        Gate::authorize('view', $document);

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
     * (e.g. /documents/typesafe/introduction). Delegates entirely to show()
     * so authorization and the rendered page/props stay identical.
     */
    public function showByPath(Request $request, DocumentNamespace $namespace, string $path): Response
    {
        $document = $namespace->documents()->where('path', $path)->firstOrFail();

        return $this->show($request, $document);
    }

    /**
     * Load the document's author and namespace, plus the sibling documents
     * in the same namespace for the sidebar navigation, shared by the show
     * view. The visibility rule mirrors DocumentNamespaceController::show:
     * the namespace's owner sees all of their own documents, everyone else
     * only sees the namespace's public ones.
     *
     * @return array{document: Document, namespaceDocuments: Collection<int, Document>}
     */
    private function forDisplay(Request $request, Document $document): array
    {
        $document->load(['user:id,name', 'namespace:id,slug,name,owner_user_id']);

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
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document): Response
    {
        Gate::authorize('update', $document);

        $document->load('adoptedSourceSnapshot');

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
     *
     * Runs synchronously for now; move to a queued job if translations of
     * longer articles make this too slow for a request/response cycle.
     */
    public function translate(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        abort_if(blank($document->source_content), 422);

        try {
            $response = (new TranslatorAgent)->prompt($document->source_content);
        } catch (\Throwable) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => '翻訳に失敗しました。しばらくしてからもう一度お試しください。',
            ]);

            return back();
        }

        if ($document->content !== $response->text) {
            $this->recordRevision($document, $request->user()->id);
        }

        $document->update(['content' => $response->text]);

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

        return to_route('documents.show', $document);
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
