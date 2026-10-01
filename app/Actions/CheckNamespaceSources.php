<?php

namespace App\Actions;

use App\Models\DocumentNamespace;
use Illuminate\Support\Collection;

class CheckNamespaceSources
{
    public function __construct(private CheckDocumentSource $checkDocumentSource) {}

    /**
     * Check every document in the namespace that has a source URL,
     * sequentially and synchronously.
     *
     * @return Collection<int, SourceCheckResult>
     */
    public function __invoke(DocumentNamespace $namespace): Collection
    {
        return $namespace->documents()
            ->whereNotNull('source_url')
            ->where('source_url', '!=', '')
            ->with('adoptedSourceSnapshot')
            ->orderBy('path')
            ->get()
            ->map($this->checkDocumentSource);
    }
}
