<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        User::factory()->create([
            'name' => 'Test User2',
            'email' => 'test2@example.com',
        ]);

        Document::factory()->for($testUser)->create([
            'title' => 'Markdownでドキュメントを書く',
            'content' => File::get(database_path('seeders/sample-document.md')),
        ]);

        Document::factory()->for($testUser)->create([
            'title' => 'Mintlifyコンポーネント記法 (inkstream2)',
            'content' => File::get(database_path('seeders/sample-document-mintlify.md')),
        ]);
    }
}
