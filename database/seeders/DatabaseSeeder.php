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

        $documents = [
            'Markdownでドキュメントを書く' => 'sample-document.md',
            'Mintlifyコンポーネント記法 (inkstream2)' => 'sample-document-mintlify.md',
            'Markdown Syntax Guide' => 'syntax-guide-index.md',
            'Extended Markdown Syntax' => 'syntax-guide-extended.md',
            'Zenn Syntax' => 'syntax-guide-zenn.md',
            'Mintlify Syntax' => 'syntax-guide-mintlify.md',
            'Thinkstream Syntax' => 'syntax-guide-thinkstream.md',
        ];

        foreach ($documents as $title => $filename) {
            Document::factory()->for($testUser)->create([
                'title' => $title,
                'content' => File::get(database_path("seeders/{$filename}")),
            ]);
        }
    }
}
