<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TranslatorAgent;
use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
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
            ->select(['id', 'owner_user_id', 'slug', 'name', 'source_url', 'created_at'])
            ->withCount('documents')
            ->latest()
            ->get();

        $documents = $request->user()->documents()
            ->whereNull('document_namespace_id')
            ->select(['id', 'user_id', 'title', 'visibility', 'created_at'])
            ->withCount('likes')
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

        $request->user()->documents()->create($validated);

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

        $this->assertUrlIsFetchable($validated['source_url']);

        try {
            $content = Http::timeout(10)->get($validated['source_url'])->throw()->body();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'source_url' => '指定のURLから本文を取得できませんでした。',
            ]);
        }

        $sourceTitle = null;

        if (preg_match('/^#\s+(.+)$/m', $content, $matches) === 1) {
            $sourceTitle = trim($matches[1]);
        }

        return Inertia::render('documents/create', [
            'fetchedSource' => [
                'source_url' => $validated['source_url'],
                'source_title' => $sourceTitle,
                'content' => $content,
            ],
        ]);
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
        $document->load(['user:id,name', 'namespace:id,slug,name']);

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
     * Overwrite the document's content with an AI translation of its source.
     *
     * Runs synchronously for now; move to a queued job if translations of
     * longer articles make this too slow for a request/response cycle.
     */
    public function translate(Document $document): RedirectResponse
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
