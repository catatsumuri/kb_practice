<?php

namespace App\Console\Commands;

use App\Actions\RestoreNamespace;
use App\Models\User;
use Illuminate\Console\Command;

class RestoreNamespaceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'namespace:restore
        {path : Path to a zip created by namespace:backup}
        {--user= : ID or email of the owner (defaults to the existing namespace\'s owner, else the first user)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore a namespace and its documents from a backup zip, overwriting documents with the same path';

    public function handle(RestoreNamespace $restoreNamespace): int
    {
        $user = null;

        if (filled($this->option('user'))) {
            $user = User::query()
                ->where('id', $this->option('user'))
                ->orWhere('email', $this->option('user'))
                ->first();

            if ($user === null) {
                $this->components->error('User not found.');

                return self::FAILURE;
            }
        }

        try {
            $result = $restoreNamespace($this->argument('path'), $user);
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $verb = $result['created'] ? 'Created' : 'Updated';

        $this->components->info("{$verb} namespace {$result['namespace']->slug} with {$result['documents']} documents.");

        return self::SUCCESS;
    }
}
