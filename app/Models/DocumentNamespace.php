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
     * Arrange documents into the namespace's navigation groups, following
     * the order of `navigation` (a list of {title, pages: [path, ...]}).
     * Pages without a matching document are skipped, empty groups dropped,
     * and documents the navigation does not mention are collected, in their
     * given order, into a trailing group without a title. Without any
     * navigation, all documents form that single untitled group.
     *
     * @param  Collection<int, Document>  $documents
     * @return list<array{title: ?string, documents: list<Document>}>
     */
    public function navigationGroups(Collection $documents): array
    {
        $documentsByPath = $documents->whereNotNull('path')->keyBy('path');
        $listedPaths = [];
        $groups = [];

        foreach ($this->navigation ?? [] as $group) {
            $groupDocuments = collect($group['pages'] ?? [])
                ->map(fn (string $path) => $documentsByPath->get($path))
                ->filter()
                ->values();

            $listedPaths = [...$listedPaths, ...$groupDocuments->pluck('path')->all()];

            if ($groupDocuments->isNotEmpty()) {
                $groups[] = ['title' => $group['title'] ?? null, 'documents' => $groupDocuments->all()];
            }
        }

        $unlisted = $documents->reject(fn (Document $document) => in_array($document->path, $listedPaths, true));

        if ($unlisted->isNotEmpty()) {
            $groups[] = ['title' => null, 'documents' => $unlisted->values()->all()];
        }

        return $groups;
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
