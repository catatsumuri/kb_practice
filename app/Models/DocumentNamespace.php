<?php

namespace App\Models;

use Database\Factories\DocumentNamespaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['slug', 'name', 'source_url', 'navigation', 'is_public'])]
class DocumentNamespace extends Model
{
    /** @use HasFactory<DocumentNamespaceFactory> */
    use HasFactory;

    /**
     * Use the slug (rather than the numeric id) for route model binding,
     * so namespace URLs are human-readable (e.g. /namespaces/typesafe).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'navigation' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Arrange documents into the namespace's navigation tree. Navigation
     * entries may be document paths or nested nodes with optional page,
     * title, label, and pages values. Missing pages and empty nodes are
     * skipped, while unlisted documents are appended in an untitled node.
     *
     * @param  Collection<int, Document>  $documents
     * @return list<array{title: ?string, document: ?Document, label: ?string, children: array}>
     */
    public function navigationTree(Collection $documents): array
    {
        $documentsByPath = $documents->whereNotNull('path')->keyBy('path');
        $listedPaths = [];
        $tree = [];

        foreach ($this->navigation ?? [] as $entry) {
            $node = $this->resolveNavigationEntry($entry, $documentsByPath, $listedPaths);

            if ($node !== null) {
                $tree[] = $node;
            }
        }

        $unlisted = $documents->reject(fn (Document $document) => in_array($document->path, $listedPaths, true));

        if ($unlisted->isNotEmpty()) {
            $tree[] = [
                'title' => null,
                'document' => null,
                'label' => null,
                'children' => $unlisted->map(fn (Document $document) => $this->documentNavigationNode($document))->values()->all(),
            ];
        }

        return $tree;
    }

    /**
     * Return documents in their configured navigation order.
     *
     * @param  Collection<int, Document>  $documents
     * @return Collection<int, Document>
     */
    public function navigationDocuments(Collection $documents): Collection
    {
        $flatten = function (array $nodes) use (&$flatten): array {
            return collect($nodes)->flatMap(function (array $node) use (&$flatten): array {
                return [
                    ...($node['document'] ? [$node['document']] : []),
                    ...$flatten($node['children']),
                ];
            })->all();
        };

        return collect($flatten($this->navigationTree($documents)));
    }

    /**
     * @param  string|array<string, mixed>  $entry
     * @param  Collection<string, Document>  $documentsByPath
     * @param  list<string>  $listedPaths
     * @return array{title: ?string, document: ?Document, label: ?string, children: array}|null
     */
    private function resolveNavigationEntry(string|array $entry, Collection $documentsByPath, array &$listedPaths): ?array
    {
        if (is_string($entry)) {
            $document = $documentsByPath->get($entry);

            if (! $document) {
                return null;
            }

            $listedPaths[] = $entry;

            return $this->documentNavigationNode($document);
        }

        $document = isset($entry['page']) && is_string($entry['page'])
            ? $documentsByPath->get($entry['page'])
            : null;

        if ($document) {
            $listedPaths[] = $document->path;
        }

        $children = [];

        foreach (is_array($entry['pages'] ?? null) ? $entry['pages'] : [] as $child) {
            if (! is_string($child) && ! is_array($child)) {
                continue;
            }

            $childNode = $this->resolveNavigationEntry($child, $documentsByPath, $listedPaths);

            if ($childNode !== null) {
                $children[] = $childNode;
            }
        }

        if (! $document && $children === []) {
            return null;
        }

        return [
            'title' => is_string($entry['title'] ?? null) ? $entry['title'] : $document?->title,
            'document' => $document,
            'label' => is_string($entry['label'] ?? null) ? $entry['label'] : null,
            'children' => $children,
        ];
    }

    /**
     * @return array{title: string, document: Document, label: null, children: array}
     */
    private function documentNavigationNode(Document $document): array
    {
        return [
            'title' => $document->title,
            'document' => $document,
            'label' => null,
            'children' => [],
        ];
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
