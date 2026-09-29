<?php

namespace App\Console\Commands;

use App\Actions\NormalizeSourceMarkdown;
use App\Models\DocumentNamespace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

#[Signature('documents:import-source
    {path : Page path on the source site (e.g. primitives or introduction/quickstart)}
    {--namespace=typesafe : Slug of the namespace whose source_url hosts the page}
    {--output= : File name to write under database/seeders (default: <namespace>-<last segment>-source.md)}')]
#[Description('Fetch a page of a namespace\'s source site and save it as normalized source Markdown under database/seeders')]
class ImportSourceCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(NormalizeSourceMarkdown $normalizeSourceMarkdown): int
    {
        $namespace = DocumentNamespace::query()->where('slug', $this->option('namespace'))->first();

        if ($namespace === null || blank($namespace->source_url)) {
            $this->components->error('The namespace does not exist or has no source_url.');

            return self::FAILURE;
        }

        $path = trim($this->argument('path'), '/');
        $url = rtrim($namespace->source_url, '/')."/{$path}.md";

        try {
            $markdown = Http::timeout(30)->get($url)->throw()->body();
            $markdown = $normalizeSourceMarkdown($markdown);
        } catch (\Throwable $e) {
            $this->components->error("Could not import {$url}: {$e->getMessage()}");

            return self::FAILURE;
        }

        $fileName = $this->option('output')
            ?: "{$namespace->slug}-".Str::afterLast($path, '/').'-source.md';

        File::put(database_path("seeders/{$fileName}"), $markdown);

        $this->components->info("Wrote database/seeders/{$fileName} (".substr_count($markdown, "\n").' lines).');

        $leftOver = collect(Str::matchAll('/^\s*<([A-Z][A-Za-z]*)\b/m', $markdown))
            ->countBy()
            ->map(fn (int $count, string $tag) => "{$tag} ×{$count}")
            ->implode(', ');

        if ($leftOver !== '') {
            $this->components->twoColumnDetail('Components left as-is', $leftOver);
        }

        return self::SUCCESS;
    }
}
