<?php

namespace App\Http\Controllers;

use App\Enums\DocumentVisibility;
use App\Models\DocumentNamespace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DocumentNamespaceController extends Controller
{
    /**
     * Display the specified resource. The owner sees all of their own
     * documents in the namespace; anyone else (including guests, for a
     * public namespace) only sees the namespace's public documents.
     */
    public function show(Request $request, DocumentNamespace $namespace): Response|RedirectResponse
    {
        Gate::authorize('view', $namespace);

        if ($request->user() === null && filled($namespace->guest_redirect_path)
            && $namespace->documents()
                ->where('path', $namespace->guest_redirect_path)
                ->where('visibility', DocumentVisibility::Public)
                ->exists()) {
            return to_route('documents.show-by-path', [
                'namespace' => $namespace,
                'path' => $namespace->guest_redirect_path,
            ]);
        }

        $isOwner = $request->user()?->id === $namespace->owner_user_id;

        $documents = $namespace->documents()
            ->when(
                $isOwner,
                fn ($query) => $query->where('user_id', $request->user()->id),
                fn ($query) => $query->where('visibility', DocumentVisibility::Public),
            )
            ->select(['id', 'user_id', 'title', 'visibility', 'created_at', 'path'])
            ->with('user:id,name')
            ->latest()
            ->get();

        return Inertia::render('namespaces/show', [
            'namespace' => $namespace,
            'documents' => $namespace->navigationDocuments($documents)->values(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', DocumentNamespace::class);

        return Inertia::render('namespaces/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', DocumentNamespace::class);

        $validated = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/',
                Rule::notIn(config('document-namespaces.reserved_slugs')),
                Rule::unique('document_namespaces', 'slug'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'is_public' => ['sometimes', 'boolean'],
        ], [
            'slug.regex' => 'スラッグは半角英数字とハイフンのみ使用できます。',
            'slug.not_in' => 'このスラッグは予約されているため使用できません。',
            'slug.unique' => 'このスラッグは既に使用されています。',
        ]);

        $validated['is_public'] = $request->boolean('is_public');

        $request->user()->documentNamespaces()->create($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'ネームスペースを作成しました',
        ]);

        return to_route('documents.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DocumentNamespace $namespace): Response
    {
        Gate::authorize('update', $namespace);

        return Inertia::render('namespaces/edit', [
            'namespace' => $namespace,
        ]);
    }

    /**
     * Update the specified resource in storage. The slug isn't editable
     * here since it's embedded in every document URL under this namespace.
     */
    public function update(Request $request, DocumentNamespace $namespace): RedirectResponse
    {
        Gate::authorize('update', $namespace);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'is_public' => ['sometimes', 'boolean'],
            'guest_redirect_path' => [
                'nullable',
                'string',
                'max:255',
                Rule::notIn(config('document-namespaces.reserved_paths')),
                Rule::exists('documents', 'path')
                    ->where('document_namespace_id', $namespace->id)
                    ->where('visibility', DocumentVisibility::Public->value),
            ],
        ], [
            'guest_redirect_path.exists' => 'この名前空間の公開記事のパスを指定してください。',
            'guest_redirect_path.not_in' => 'このパスは転送先に指定できません。',
        ]);

        $validated['is_public'] = $request->boolean('is_public');

        $namespace->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'ネームスペースを更新しました',
        ]);

        return to_route('namespaces.show', $namespace);
    }
}
