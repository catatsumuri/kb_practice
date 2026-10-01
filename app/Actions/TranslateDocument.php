<?php

namespace App\Actions;

use App\Ai\Agents\TranslatorAgent;
use App\Models\Document;

class TranslateDocument
{
    public function __construct(
        private CompressTranslationLinks $compressTranslationLinks,
        private SplitMarkdownForTranslation $splitMarkdownForTranslation,
    ) {}

    /**
     * Overwrite the document's content with an AI translation of its source,
     * recording the previous content as a revision when it changes.
     *
     * A long source is split into chunks translated one after another, so
     * no single request outlives the provider timeout; nothing is written
     * unless every chunk succeeds.
     *
     * Runs synchronously; move to a queued job if translations of longer
     * articles make this too slow for a request/response cycle.
     *
     * @throws \Throwable When a translation request fails.
     */
    public function __invoke(Document $document, ?int $userId): TranslationResult
    {
        $translationSource = $this->compressTranslationLinks->compress($document->translationSource());
        $chunks = ($this->splitMarkdownForTranslation)($translationSource['source']);

        $translatedChunks = [];
        $model = null;
        $inputTokens = 0;
        $outputTokens = 0;

        foreach ($chunks as $chunk) {
            $response = (new TranslatorAgent)->prompt($chunk['text']);

            $translatedChunks[] = $response->text;
            $model ??= $response->meta->model;
            $inputTokens += $response->usage->inputTokens;
            $outputTokens += $response->usage->outputTokens;
        }

        $translatedContent = $this->compressTranslationLinks->restore(
            ($this->splitMarkdownForTranslation)->join($chunks, $translatedChunks),
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

        return new TranslationResult($model, $inputTokens, $outputTokens, count($chunks));
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
