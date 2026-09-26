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

#[Fillable(['title', 'content', 'visibility', 'document_type', 'source_title', 'source_url', 'source_author', 'source_content'])]
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
}
