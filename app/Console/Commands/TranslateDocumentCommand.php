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
            $translateDocument($document, null);
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Translated {$namespaceSlug}/{$path}.");

        return self::SUCCESS;
    }
}
