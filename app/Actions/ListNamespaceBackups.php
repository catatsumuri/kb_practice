<?php

namespace App\Actions;

use App\Models\DocumentNamespace;
use ZipArchive;

class ListNamespaceBackups
{
    /**
     * @return list<array{filename: string, namespace_slug: string, created_at: ?string, description: ?string, size_bytes: int, document_count: int, with_snapshots: bool, with_revisions: bool}>
     */
    public function __invoke(?DocumentNamespace $namespace = null): array
    {
        $backups = [];

        foreach (glob(BackupNamespace::directory().'/*.zip') ?: [] as $path) {
            if (! is_file($path) || is_link($path)) {
                continue;
            }

            $zip = new ZipArchive;

            if ($zip->open($path) !== true) {
                continue;
            }

            try {
                $namespaceData = json_decode($zip->getFromName(BackupNamespace::NAMESPACE_MANIFEST) ?: '', true);
                $manifest = json_decode($zip->getFromName(BackupNamespace::BACKUP_MANIFEST) ?: '', true);

                if (! is_array($namespaceData) || ! is_string($namespaceData['slug'] ?? null)
                    || ($namespace !== null && $namespaceData['slug'] !== $namespace->slug)
                    || ! is_array($manifest) || ($manifest['format_version'] ?? null) !== BackupNamespace::FORMAT_VERSION) {
                    continue;
                }

                $documentCount = 0;

                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $name = $zip->getNameIndex($index);

                    if (is_string($name) && str_starts_with($name, BackupNamespace::META_DIRECTORY) && str_ends_with($name, '.json')) {
                        $documentCount++;
                    }
                }

                $backups[] = [
                    'filename' => basename($path),
                    'namespace_slug' => $namespaceData['slug'],
                    'created_at' => is_string($manifest['created_at'] ?? null) ? $manifest['created_at'] : null,
                    'description' => is_string($manifest['description'] ?? null) ? $manifest['description'] : null,
                    'size_bytes' => (int) filesize($path),
                    'document_count' => $documentCount,
                    'with_snapshots' => ($manifest['with_snapshots'] ?? false) === true,
                    'with_revisions' => ($manifest['with_revisions'] ?? false) === true,
                ];
            } finally {
                $zip->close();
            }
        }

        usort($backups, fn (array $left, array $right): int => (strtotime($right['created_at'] ?? '') ?: 0) <=> (strtotime($left['created_at'] ?? '') ?: 0)
            ?: strcmp($right['filename'], $left['filename']));

        return $backups;
    }
}
