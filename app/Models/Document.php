<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'content', 'visibility', 'document_type', 'source_title', 'source_url', 'canonical_url', 'source_author', 'source_content', 'document_namespace_id', 'path', 'document_source_snapshot_id'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'visibility' => 'private',
        'document_type' => 'original',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => DocumentVisibility::class,
            'document_type' => DocumentType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<DocumentLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(DocumentLike::class);
    }

    /**
     * @return BelongsTo<DocumentNamespace, $this>
     */
    public function namespace(): BelongsTo
    {
        return $this->belongsTo(DocumentNamespace::class, 'document_namespace_id');
    }

    /**
     * @return HasMany<DocumentSourceSnapshot, $this>
     */
    public function sourceSnapshots(): HasMany
    {
        return $this->hasMany(DocumentSourceSnapshot::class);
    }

    /**
     * Past versions of this document's title/content, recorded whenever an
     * edit or AI translation overwrites them.
     *
     * @return HasMany<DocumentRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(DocumentRevision::class);
    }

    /**
     * The source text to hand to the translator: source_content without a
     * leading blockquote that precedes the first heading (Mintlify's
     * "Documentation Index" notice), which is not part of the article.
     */
    public function translationSource(): string
    {
        return preg_replace('/\A(?:>[^\n]*\n|\n)+(?=#\s)/', '', (string) $this->source_content);
    }

    /**
     * The source snapshot currently backing this document's source_content,
     * i.e. the version the user has explicitly adopted. Not necessarily the
     * most recent row in sourceSnapshots() — a newer snapshot may exist but
     * not yet be adopted, pending the user's review of its diff.
     *
     * @return BelongsTo<DocumentSourceSnapshot, $this>
     */
    public function adoptedSourceSnapshot(): BelongsTo
    {
        return $this->belongsTo(DocumentSourceSnapshot::class, 'document_source_snapshot_id');
    }
}
