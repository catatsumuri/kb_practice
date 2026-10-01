<?php

namespace App\Console\Commands;

use App\Actions\CheckDocumentSource;
use App\Actions\CheckNamespaceSources;
use App\Actions\SourceCheckResult;
use App\Enums\SourceCheckStatus;
use App\Models\Document;
use App\Models\DocumentNamespace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('documents:check-sources
    {--namespace= : Slug of the namespace to check (default: every namespace)}
    {--document= : ID of a single document to check}')]
#[Description('Re-fetch documents\' source URLs and record new snapshots when the source has changed')]
class CheckSourcesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CheckDocumentSource $checkDocumentSource, CheckNamespaceSources $checkNamespaceSources): int
    {
        if ($this->option('document') !== null) {
            $document = Document::query()->find($this->option('document'));

            if ($document === null) {
                $this->components->error('The document does not exist.');

                return self::FAILURE;
            }

            $results = collect([$checkDocumentSource($document)]);
        } else {
            $namespaces = DocumentNamespace::query()
                ->when($this->option('namespace') !== null, fn ($query) => $query->where('slug', $this->option('namespace')))
                ->get();

            if ($namespaces->isEmpty()) {
                $this->components->error('No matching namespace found.');

                return self::FAILURE;
            }

            $results = $namespaces->flatMap($checkNamespaceSources);
        }

        return $this->report($results);
    }

    /**
     * @param  Collection<int, SourceCheckResult>  $results
     */
    private function report(Collection $results): int
    {
        foreach ($results as $result) {
            $label = $result->document->path ?? "#{$result->document->id}";

            $this->components->twoColumnDetail(
                $label,
                $result->message !== null ? "{$result->status->value}: {$result->message}" : $result->status->value,
            );
        }

        $counts = $results->countBy(fn (SourceCheckResult $result) => $result->status->value);

        $this->components->info(sprintf(
            'Updated %d, pending %d, unchanged %d, skipped %d, failed %d.',
            $counts->get(SourceCheckStatus::Updated->value, 0),
            $counts->get(SourceCheckStatus::Pending->value, 0),
            $counts->get(SourceCheckStatus::Unchanged->value, 0),
            $counts->get(SourceCheckStatus::Skipped->value, 0),
            $counts->get(SourceCheckStatus::Failed->value, 0),
        ));

        return $counts->has(SourceCheckStatus::Failed->value) ? self::FAILURE : self::SUCCESS;
    }
}
