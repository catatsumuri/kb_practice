<?php

namespace App\Actions;

use App\Ai\Agents\TranslatorAgent;
use App\Models\Document;

class TranslateDocument
{
    /**
     * Overwrite the document's content with an AI translation of its source,
     * recording the previous content as a revision when it changes.
     *
     * Runs synchronously; move to a queued job if translations of longer
     * articles make this too slow for a request/response cycle.
     *
     * @throws \Throwable When the translation request fails.
     */
    public function __invoke(Document $document, ?int $userId): void
    {
        $response = (new TranslatorAgent)->prompt($document->translationSource());

        if ($document->content !== $response->text) {
            $document->revisions()->create([
                'user_id' => $userId,
                'title' => $document->title,
                'content' => $document->content,
            ]);
        }

        $document->update([
            'title' => $this->leadingHeading($response->text) ?? $document->title,
            'content' => $response->text,
        ]);
    }

    /**
     * The text of a leading "# Heading" line, without a trailing "{#anchor}".
     */
    private function leadingHeading(string $markdown): ?string
    {
        if (preg_match('/\A\s*#\s+(.+?)(?:\s*\{#[^}]*\})?\s*$/m', $markdown, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
