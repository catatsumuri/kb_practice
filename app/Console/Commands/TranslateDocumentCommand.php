<?php

namespace App\Console\Commands;

use App\Actions\TranslateDocument;
use App\Models\Document;
use App\Models\DocumentNamespace;
use Illuminate\Console\Command;

class TranslateDocumentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:translate
        {address : Namespace slug followed by the document path (e.g. typesafe/introduction/quickstart)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Overwrite a document\'s content with an AI translation of its source';

    public function handle(TranslateDocument $translateDocument): int
    {
        [$namespaceSlug, $path] = array_pad(explode('/', trim($this->argument('address'), '/'), 2), 2, null);

        $document = Document::query()
            ->where('path', $path)
            ->whereIn('document_namespace_id', DocumentNamespace::query()->where('slug', $namespaceSlug)->select('id'))
            ->first();

        if ($document === null) {
            $this->components->error('Document not found.');

            return self::FAILURE;
        }

        if (blank($document->source_content)) {
            $this->components->error('The document has no source content to translate.');

            return self::FAILURE;
        }

        try {
            $result = $translateDocument($document, null);
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Translated {$namespaceSlug}/{$path}.");
        if ($result->chunkCount > 1) {
            $this->components->twoColumnDetail('Chunks', (string) $result->chunkCount);
        }

        $this->components->twoColumnDetail('Model', $result->model ?? 'unknown');
        $this->components->twoColumnDetail('Input tokens', number_format($result->inputTokens));
        $this->components->twoColumnDetail('Output tokens', number_format($result->outputTokens));

        $estimatedCost = $this->estimatedCost(
            $result->model,
            $result->inputTokens,
            $result->outputTokens,
        );

        $this->components->twoColumnDetail(
            'Estimated cost',
            $estimatedCost === null ? 'unavailable' : '$'.number_format($estimatedCost, 6),
        );

        return self::SUCCESS;
    }

    private function estimatedCost(?string $model, int $inputTokens, int $outputTokens): ?float
    {
        $pricing = config('ai.providers.bedrock.pricing');

        if (
            ! is_array($pricing)
            || $model === null
            || $model !== ($pricing['model'] ?? null)
            || ! is_numeric($pricing['input_per_million_tokens'] ?? null)
            || ! is_numeric($pricing['output_per_million_tokens'] ?? null)
        ) {
            return null;
        }

        return ($inputTokens * (float) $pricing['input_per_million_tokens']
            + $outputTokens * (float) $pricing['output_per_million_tokens']) / 1_000_000;
    }
}
