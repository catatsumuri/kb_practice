<?php

namespace App\Http\Controllers;

use App\Actions\BackupNamespace;
use App\Actions\ListNamespaceBackups;
use App\Models\DocumentNamespace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NamespaceBackupController extends Controller
{
    public function index(DocumentNamespace $namespace, ListNamespaceBackups $listBackups): Response
    {
        Gate::authorize('update', $namespace);

        return Inertia::render('namespaces/backups', [
            'namespace' => $namespace->only(['slug', 'name']),
            'backups' => $listBackups($namespace),
        ]);
    }

    public function download(DocumentNamespace $namespace, string $backup, ListNamespaceBackups $listBackups): BinaryFileResponse
    {
        Gate::authorize('update', $namespace);

        $record = collect($listBackups($namespace))->firstWhere('filename', $backup);

        abort_if($record === null, 404);

        return response()->download(BackupNamespace::directory().'/'.$record['filename'], $record['filename'], [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function store(Request $request, DocumentNamespace $namespace, BackupNamespace $backupNamespace): RedirectResponse
    {
        Gate::authorize('update', $namespace);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'description.string' => '説明は文字列で入力してください。',
            'description.max' => '説明は2000文字以内で入力してください。',
        ]);

        $path = substr(BackupNamespace::defaultPath($namespace), 0, -4).'-'.Str::random(8).'.zip';
        $count = $backupNamespace(
            $namespace,
            $path,
            withSnapshots: true,
            withRevisions: true,
            description: $validated['description'] ?? null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$count}記事のフルバックアップを作成しました。",
        ]);

        return to_route('namespaces.backups.index', $namespace);
    }

    public function destroy(DocumentNamespace $namespace, string $backup, ListNamespaceBackups $listBackups): RedirectResponse
    {
        Gate::authorize('update', $namespace);

        $record = collect($listBackups($namespace))->firstWhere('filename', $backup);

        abort_if($record === null, 404);

        if (! File::delete(BackupNamespace::directory().'/'.$record['filename'])) {
            return to_route('namespaces.backups.index', $namespace)->withErrors([
                'backup' => 'バックアップを削除できませんでした。もう一度お試しください。',
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'バックアップを削除しました。',
        ]);

        return to_route('namespaces.backups.index', $namespace);
    }
}
