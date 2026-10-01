<?php

namespace Database\Seeders;

use App\Actions\BackupNamespace;
use App\Actions\ListNamespaceBackups;
use App\Actions\RestoreNamespace;
use App\Enums\DocumentVisibility;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]),
            User::factory()->create([
                'name' => 'Test User2',
                'email' => 'test2@example.com',
            ]),
        ];

        $this->restoreNamespaces($users[0]);
    }

    /**
     * Restore the latest backup of each namespace, owned by the first
     * seeded user. Backups are ordered by their archive creation time.
     */
    private function restoreNamespaces(User $owner): void
    {
        $backups = collect(app(ListNamespaceBackups::class)())
            ->unique('namespace_slug');

        if ($backups->isEmpty()) {
            $this->command->warn('Skipping namespaces: no supported backups in '.BackupNamespace::directory().'.');

            return;
        }

        foreach ($backups as $backup) {
            $result = app(RestoreNamespace::class)(BackupNamespace::directory().'/'.$backup['filename'], $owner);
            $namespace = $result['namespace'];
            $defaultPath = config('document-namespaces.seed_guest_redirect_paths.'.$namespace->slug);

            if ($namespace->guest_redirect_path === null && is_string($defaultPath)
                && $namespace->documents()->where('path', $defaultPath)->where('visibility', DocumentVisibility::Public)->exists()) {
                $namespace->update(['guest_redirect_path' => $defaultPath]);
            }

            $this->command->info('Restored '.$backup['namespace_slug'].' from '.$backup['filename'].'.');
        }
    }
}
