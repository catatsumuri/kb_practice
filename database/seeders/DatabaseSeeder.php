<?php

namespace Database\Seeders;

use App\Actions\RestoreNamespace;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Where the typesafe namespace backup is placed for seeding. It isn't
     * committed (storage/app/private is gitignored); create it with
     * `artisan namespace:backup typesafe` and copy it here.
     */
    public const TYPESAFE_BACKUP = 'app/private/seeds/typesafe.zip';

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

        $this->restoreTypesafeNamespace($users[0]);
    }

    /**
     * Restore the typesafe namespace from its backup archive, owned by the
     * given user. Skipped with a warning when the archive isn't present.
     */
    private function restoreTypesafeNamespace(User $owner): void
    {
        $path = storage_path(self::TYPESAFE_BACKUP);

        if (! File::exists($path)) {
            $this->command?->warn('Skipping typesafe: no backup at storage/'.self::TYPESAFE_BACKUP.'.');

            return;
        }

        app(RestoreNamespace::class)($path, $owner);
    }
}
