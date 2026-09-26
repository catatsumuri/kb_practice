<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
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

        Document::factory()->for($users[0])->create([
            'title' => 'はじめに（TypeSafe AI ドキュメント日本語訳）',
            'content' => File::get(database_path('seeders/sample-translation.md')),
            'visibility' => DocumentVisibility::Public,
            'document_type' => DocumentType::Translation,
            'source_title' => 'Introduction',
            'source_url' => 'https://docs.typesafe.ai/introduction.md',
            'source_author' => 'TypeSafe',
            'source_content' => File::get(database_path('seeders/sample-source.md')),
        ]);
    }
}
