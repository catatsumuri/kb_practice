<?php

namespace App\Actions;

use App\Models\Document;
use App\Models\DocumentNamespace;
use RuntimeException;
use ZipArchive;

class BackupNamespace
{
    public const FORMAT_VERSION = 1;

    public const BACKUP_MANIFEST = '_backup.json';

    public const NAMESPACE_MANIFEST = '_namespace.json';

    /**
     * Directory holding each document's translated content, as
     * "content/{path}.md". Keyed by document path so that nested paths
     * (e.g. sdk/javascript/api/classes/TypeSafeClient) become nested
     * directories inside the archive.
     */
    public const CONTENT_DIRECTORY = 'content/';

    /** Directory holding each document's source Markdown, as "source/{path}.md". */
    public const SOURCE_DIRECTORY = 'source/';

    /** Directory holding each document's metadata, as "meta/{path}.json". */
    public const META_DIRECTORY = 'meta/';

    public static function directory(): string
    {
        return storage_path('app/private/backups');
    }

    public static function defaultPath(DocumentNamespace $namespace): string
    {
        return self::directory().'/'.$namespace->slug.'-'.now()->format('Ymd-His').'.zip';
    }

    /**
     * Write the namespace and all of its documents (every visibility, so
     * the backup is complete) into a zip archive. Source snapshots and
     * revisions are bulky, so they are only included on request.
     *
     * @return int The number of documents backed up.
     */
    public function __invoke(
        DocumentNamespace $namespace,
        string $zipPath,
        bool $withSnapshots = false,
        bool $withRevisions = false,
        ?string $description = null,
    ): int {
        $directory = dirname($zipPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Failed to create zip: {$zipPath}");
        }

        $zip->addFromString(self::BACKUP_MANIFEST, $this->toJson([
            'format_version' => self::FORMAT_VERSION,
            'created_at' => now()->toIso8601String(),
            'description' => filled($description) ? $description : null,
            'with_snapshots' => $withSnapshots,
            'with_revisions' => $withRevisions,
        ]));

        $zip->addFromString(self::NAMESPACE_MANIFEST, $this->toJson([
            'slug' => $namespace->slug,
            'name' => $namespace->name,
            'source_url' => $namespace->source_url,
            'is_public' => $namespace->is_public,
            'navigation' => $namespace->navigation,
            'guest_redirect_path' => $namespace->guest_redirect_path,
        ]));

        $documents = $namespace->documents()
            ->whereNotNull('path')
            ->with([
                ...($withSnapshots ? ['sourceSnapshots'] : []),
                ...($withRevisions ? ['revisions'] : []),
            ])
            ->orderBy('path')
            ->get();

        foreach ($documents as $document) {
            $this->addDocument($zip, $document, $withSnapshots, $withRevisions);
        }

        $zip->close();

        return $documents->count();
    }

    private function addDocument(ZipArchive $zip, Document $document, bool $withSnapshots, bool $withRevisions): void
    {
        $meta = [
            'title' => $document->title,
            'visibility' => $document->visibility->value,
            'document_type' => $document->document_type->value,
            'source_title' => $document->source_title,
            'source_url' => $document->source_url,
            'canonical_url' => $document->canonical_url,
            'source_author' => $document->source_author,
        ];

        if ($withSnapshots) {
            $meta['snapshots'] = $document->sourceSnapshots->sortBy('id')->map(fn ($snapshot) => [
                'content' => $snapshot->content,
                'content_hash' => $snapshot->content_hash,
                'title' => $snapshot->title,
                'fetched_at' => $snapshot->fetched_at->toIso8601String(),
                'adopted' => $snapshot->id === $document->document_source_snapshot_id,
            ])->values()->all();
        }

        if ($withRevisions) {
            $meta['revisions'] = $document->revisions->sortBy('id')->map(fn ($revision) => [
                'title' => $revision->title,
                'content' => $revision->content,
                'created_at' => $revision->created_at->toIso8601String(),
            ])->values()->all();
        }

        $zip->addFromString(self::CONTENT_DIRECTORY.$document->path.'.md', $document->content);
        $zip->addFromString(self::META_DIRECTORY.$document->path.'.json', $this->toJson($meta));

        if (filled($document->source_content)) {
            $zip->addFromString(self::SOURCE_DIRECTORY.$document->path.'.md', $document->source_content);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function toJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
