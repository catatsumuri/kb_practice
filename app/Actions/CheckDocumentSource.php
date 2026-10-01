<?php

namespace App\Actions;

use App\Enums\SourceCheckStatus;
use App\Models\Document;
use Illuminate\Validation\ValidationException;

class CheckDocumentSource
{
    public function __construct(
        private FetchSourceContent $fetchSourceContent,
        private NormalizeSourceMarkdown $normalizeSourceMarkdown,
    ) {}

    /**
     * Re-fetch the document's source URL and, if the content differs from
     * the adopted snapshot, record a new (not-yet-adopted) snapshot for the
     * user to review as a diff. A change that was already recorded by an
     * earlier check and is still awaiting adoption is reported as Pending
     * instead of piling up duplicate snapshots. The fetched Markdown is normalized first,
     * since the stored source is, so an untouched page compares equal.
     */
    public function __invoke(Document $document): SourceCheckResult
    {
        if (blank($document->source_url)) {
            return new SourceCheckResult($document, SourceCheckStatus::Skipped);
        }

        try {
            $fetched = ($this->fetchSourceContent)($document->source_url);
            $fetched['content'] = ($this->normalizeSourceMarkdown)($fetched['content']);
        } catch (\RuntimeException $exception) {
            return new SourceCheckResult($document, SourceCheckStatus::Failed, $exception->getMessage());
        } catch (ValidationException $exception) {
            return new SourceCheckResult(
                $document,
                SourceCheckStatus::Failed,
                $exception->errors()['source_url'][0] ?? '原文の取得に失敗しました。',
            );
        }

        $hash = hash('sha256', $fetched['content']);

        if ($hash === $document->adoptedSourceSnapshot?->content_hash) {
            return new SourceCheckResult($document, SourceCheckStatus::Unchanged);
        }

        $latest = $document->sourceSnapshots()->latest('fetched_at')->latest('id')->first();

        if ($latest?->content_hash === $hash) {
            return new SourceCheckResult($document, SourceCheckStatus::Pending);
        }

        $document->recordSourceSnapshot($fetched['content'], $fetched['title']);

        return new SourceCheckResult($document, SourceCheckStatus::Updated);
    }
}
