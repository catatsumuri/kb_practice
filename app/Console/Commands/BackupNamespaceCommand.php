<?php

namespace App\Console\Commands;

use App\Actions\BackupNamespace;
use App\Models\DocumentNamespace;
use Illuminate\Console\Command;

class BackupNamespaceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'namespace:backup
        {namespace : Namespace slug}
        {--output= : Output zip path (defaults to a timestamped zip under storage/app/private/backups)}
        {--description= : Optional note stored in the archive manifest}
        {--with-snapshots : Include source snapshots}
        {--with-revisions : Include document revision history}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Back up a namespace and all of its documents as a zip archive';

    public function handle(BackupNamespace $backupNamespace): int
    {
        $namespace = DocumentNamespace::query()->where('slug', $this->argument('namespace'))->first();

        if ($namespace === null) {
            $this->components->error('Namespace not found.');

            return self::FAILURE;
        }

        $zipPath = $this->option('output') ?: BackupNamespace::defaultPath($namespace);

        try {
            $count = $backupNamespace(
                $namespace,
                $zipPath,
                withSnapshots: (bool) $this->option('with-snapshots'),
                withRevisions: (bool) $this->option('with-revisions'),
                description: $this->option('description'),
            );
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Backed up {$namespace->slug} ({$count} documents) to:");
        $this->line($zipPath);

        return self::SUCCESS;
    }
}
