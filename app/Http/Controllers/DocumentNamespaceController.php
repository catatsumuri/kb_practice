<?php

namespace App\Http\Controllers;

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
     * Display the specified resource.
     */
    public function show(Request $request, DocumentNamespace $namespace): Response
    {
        Gate::authorize('view', $namespace);

        $documents = $namespace->documents()
            ->where('user_id', $request->user()->id)
            ->select(['id', 'user_id', 'title', 'visibility', 'created_at', 'path'])
            ->withCount('likes')
            ->with('user:id,name')
            ->latest()
            ->get();

        return Inertia::render('namespaces/show', [
            'namespace' => $namespace,
            'documents' => $documents,
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
        ], [
            'slug.regex' => 'スラッグは半角英数字とハイフンのみ使用できます。',
            'slug.not_in' => 'このスラッグは予約されているため使用できません。',
            'slug.unique' => 'このスラッグは既に使用されています。',
        ]);

        $request->user()->documentNamespaces()->create($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'ネームスペースを作成しました',
        ]);

        return to_route('documents.index');
    }
}
