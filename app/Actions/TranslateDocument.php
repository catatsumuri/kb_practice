<?php

namespace App\Actions;

use App\Ai\Agents\TranslatorAgent;
use App\Models\Document;
use Laravel\Ai\Responses\AgentResponse;

class TranslateDocument
{
    public function __construct(private CompressTranslationLinks $compressTranslationLinks) {}

    /**
     * Overwrite the document's content with an AI translation of its source,
     * recording the previous content as a revision when it changes.
     *
     * Runs synchronously; move to a queued job if translations of longer
     * articles make this too slow for a request/response cycle.
     *
     * @throws \Throwable When the translation request fails.
     */
    public function __invoke(Document $document, ?int $userId): AgentResponse
    {
        $translationSource = $this->compressTranslationLinks->compress($document->translationSource());
        $response = (new TranslatorAgent)->prompt($translationSource['source']);
        $translatedContent = $this->compressTranslationLinks->restore(
            $response->text,
            $translationSource['replacements'],
        );

        if ($document->content !== $translatedContent) {
            $document->revisions()->create([
                'user_id' => $userId,
                'title' => $document->title,
                'content' => $document->content,
            ]);
        }

        $document->update([
            'title' => $this->leadingHeading($translatedContent) ?? $document->title,
            'content' => $translatedContent,
        ]);

        return $response;
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
