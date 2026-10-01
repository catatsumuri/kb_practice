<?php

namespace App\Actions;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;
use ZipArchive;

class RestoreNamespace
{
    /**
     * Restore a namespace backup, creating the namespace if it doesn't
     * exist and otherwise updating it in place. Documents are matched by
     * path: existing ones are overwritten, new ones created, and documents
     * absent from the archive are left alone. The whole restore runs in one
     * transaction, so a malformed archive changes nothing.
     *
     * @return array{namespace: DocumentNamespace, created: bool, documents: int}
     */
    public function __invoke(string $zipPath, ?User $user = null): array
    {
        $zip = $this->open($zipPath);

        try {
            $this->assertSafeEntries($zip);
            $this->assertSupportedFormat($zip);

            $namespaceData = $this->readJson($zip, BackupNamespace::NAMESPACE_MANIFEST);
            $slug = $namespaceData['slug'] ?? null;

            if (! is_string($slug) || preg_match('/\A[a-z][a-z0-9]*(?:-[a-z0-9]+)*\z/', $slug) !== 1 || ! is_string($namespaceData['name'] ?? null)) {
                throw new RuntimeException('The archive has invalid namespace metadata.');
            }

            return DB::transaction(function () use ($zip, $namespaceData, $slug, $user): array {
                $namespace = DocumentNamespace::query()->where('slug', $slug)->first();
                $owner = $user ?? $namespace?->owner ?? User::query()->orderBy('id')->first();

                if ($owner === null) {
                    throw new RuntimeException('Cannot restore without an existing user.');
                }

                if ($namespace !== null && $namespace->owner_user_id !== $owner->id) {
                    throw new RuntimeException("The namespace [{$slug}] belongs to a different user.");
                }

                if ($namespace === null && in_array($slug, config('document-namespaces.reserved_slugs'), true)) {
                    throw new RuntimeException("The slug [{$slug}] is reserved.");
                }

                $attributes = [
                    'name' => $namespaceData['name'],
                    'source_url' => $namespaceData['source_url'] ?? null,
                    'is_public' => (bool) ($namespaceData['is_public'] ?? false),
                    'navigation' => is_array($namespaceData['navigation'] ?? null) ? $namespaceData['navigation'] : null,
                ];

                $created = $namespace === null;
                $namespace = $created
                    ? $owner->documentNamespaces()->create(['slug' => $slug, ...$attributes])
                    : tap($namespace)->update($attributes);

                $documents = 0;

                foreach ($this->documentPaths($zip) as $path) {
                    $this->restoreDocument($zip, $namespace, $owner, $path);
                    $documents++;
                }

                return ['namespace' => $namespace, 'created' => $created, 'documents' => $documents];
            });
        } finally {
            $zip->close();
        }
    }

    private function restoreDocument(ZipArchive $zip, DocumentNamespace $namespace, User $owner, string $path): void
    {
        $meta = $this->readJson($zip, BackupNamespace::META_DIRECTORY.$path.'.json');
        $content = $zip->getFromName(BackupNamespace::CONTENT_DIRECTORY.$path.'.md');
        $visibility = DocumentVisibility::tryFrom((string) ($meta['visibility'] ?? ''));
        $type = DocumentType::tryFrom((string) ($meta['document_type'] ?? ''));

        if ($content === false || ! is_string($meta['title'] ?? null) || $visibility === null || $type === null) {
            throw new RuntimeException("The archive has an invalid document: {$path}");
        }

        $source = $zip->getFromName(BackupNamespace::SOURCE_DIRECTORY.$path.'.md');

        $document = $namespace->documents()->where('path', $path)->first() ?? new Document;

        $document->fill([
            'path' => $path,
            'title' => $meta['title'],
            'content' => $content,
            'visibility' => $visibility,
            'document_type' => $type,
            'source_title' => $meta['source_title'] ?? null,
            'source_url' => $meta['source_url'] ?? null,
            'canonical_url' => $meta['canonical_url'] ?? null,
            'source_author' => $meta['source_author'] ?? null,
            'source_content' => $source === false ? null : $source,
        ]);

        $document->user()->associate($document->user_id ?? $owner->id);
        $document->namespace()->associate($namespace);
        $document->save();

        if (is_array($meta['snapshots'] ?? null)) {
            $this->restoreSnapshots($document, $meta['snapshots']);
        }

        if (is_array($meta['revisions'] ?? null)) {
            $this->restoreRevisions($document, $meta['revisions']);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $snapshots
     */
    private function restoreSnapshots(Document $document, array $snapshots): void
    {
        $document->update(['document_source_snapshot_id' => null]);
        $document->sourceSnapshots()->delete();

        $adoptedId = null;

        foreach ($snapshots as $data) {
            $snapshot = $document->sourceSnapshots()->create([
                'content' => $data['content'],
                'content_hash' => $data['content_hash'],
                'title' => $data['title'] ?? null,
                'fetched_at' => $data['fetched_at'],
            ]);

            if (($data['adopted'] ?? false) === true) {
                $adoptedId = $snapshot->id;
            }
        }

        $document->update(['document_source_snapshot_id' => $adoptedId]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $revisions
     */
    private function restoreRevisions(Document $document, array $revisions): void
    {
        $document->revisions()->delete();

        foreach ($revisions as $data) {
            $revision = $document->revisions()->make([
                'title' => $data['title'],
                'content' => $data['content'],
            ]);

            $revision->forceFill(['created_at' => $data['created_at']])->save();
        }
    }

    private function open(string $zipPath): ZipArchive
    {
        if (! is_file($zipPath)) {
            throw new RuntimeException("Zip file not found: {$zipPath}");
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Failed to open zip: {$zipPath}");
        }

        return $zip;
    }

    private function assertSupportedFormat(ZipArchive $zip): void
    {
        $manifest = $this->readJson($zip, BackupNamespace::BACKUP_MANIFEST);

        if (($manifest['format_version'] ?? null) !== BackupNamespace::FORMAT_VERSION) {
            throw new RuntimeException('Unsupported backup format version.');
        }
    }

    /**
     * Document paths listed by the archive's meta entries, in archive order.
     *
     * @return list<string>
     */
    private function documentPaths(ZipArchive $zip): array
    {
        $paths = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (str_starts_with($name, BackupNamespace::META_DIRECTORY) && str_ends_with($name, '.json')) {
                $paths[] = substr($name, strlen(BackupNamespace::META_DIRECTORY), -strlen('.json'));
            }
        }

        return $paths;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(ZipArchive $zip, string $name): array
    {
        $raw = $zip->getFromName($name);

        if ($raw === false) {
            throw new RuntimeException("The archive is missing {$name}.");
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException("The archive has invalid JSON in {$name}.");
        }

        if (! is_array($data)) {
            throw new RuntimeException("The archive has invalid JSON in {$name}.");
        }

        return $data;
    }

    private function assertSafeEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (! is_string($name) || str_contains($name, "\0") || str_contains($name, '\\')) {
                throw new RuntimeException('The archive contains an invalid file path.');
            }

            foreach (explode('/', rtrim($name, '/')) as $segment) {
                if ($segment === '' || $segment === '.' || $segment === '..') {
                    throw new RuntimeException('The archive contains an invalid file path.');
                }
            }
        }
    }
}
