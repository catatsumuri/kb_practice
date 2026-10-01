<?php

namespace App\Http\Controllers;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'view' => ['nullable', 'in:overview,all'],
            'scope' => ['nullable', 'in:all,standalone'],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'q.string' => '検索語は文字列で入力してください。',
            'q.max' => '検索語は200文字以内で入力してください。',
            'view.in' => '表示モードが不正です。',
            'scope.in' => '絞り込み範囲が不正です。',
            'page.integer' => 'ページ番号は整数で指定してください。',
            'page.min' => 'ページ番号は1以上で指定してください。',
        ]);

        $search = trim($validated['q'] ?? '');
        $view = $search !== '' ? 'all' : ($validated['view'] ?? 'overview');
        $scope = $validated['scope'] ?? 'all';
        $documents = Document::query()
            ->select(['id', 'user_id', 'document_namespace_id', 'path', 'title', 'updated_at'])
            ->where('visibility', DocumentVisibility::Public)
            ->with(['user:id,name', 'namespace:id,slug,name'])
            ->latest('updated_at')
            ->orderByDesc('id');

        $standaloneDocuments = (clone $documents)->whereNull('document_namespace_id');
        $collections = $view === 'overview'
            ? DocumentNamespace::query()
                ->select(['id', 'slug', 'name', 'owner_user_id'])
                ->where(fn (Builder $query) => $query
                    ->where('is_public', true)
                    ->orWhere('owner_user_id', $request->user()->id))
                ->whereHas('documents', fn (Builder $query) => $query->where('visibility', DocumentVisibility::Public))
                ->withCount(['documents' => fn (Builder $query) => $query->where('visibility', DocumentVisibility::Public)])
                ->with('owner:id,name')
                ->orderBy('name')
                ->get()
            : [];

        $results = null;

        if ($view === 'all') {
            $results = (clone $documents)
                ->when($scope === 'standalone', fn (Builder $query) => $query->whereNull('document_namespace_id'))
                ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                    ->whereLike('title', '%'.$search.'%')
                    ->orWhereLike('path', '%'.$search.'%')))
                ->paginate(20)
                ->appends(['view' => 'all', 'scope' => $scope, 'q' => $search]);
        }

        return Inertia::render('dashboard', [
            'filters' => ['q' => $search, 'view' => $view, 'scope' => $scope],
            'collections' => $collections,
            'recentDocuments' => $view === 'overview' ? (clone $documents)->limit(10)->get() : [],
            'standaloneDocuments' => $view === 'overview' ? (clone $standaloneDocuments)->limit(6)->get() : [],
            'standaloneCount' => (clone $standaloneDocuments)->count(),
            'documents' => $results,
        ]);
    }
}
