<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
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

        $typesafeNamespace = DocumentNamespace::factory()->create([
            'owner_user_id' => $users[0]->id,
            'slug' => 'typesafe',
            'name' => 'TypeSafe AI Docs',
            'source_url' => 'https://docs.typesafe.ai',
            'is_public' => true,
        ]);

        $sourceContent = File::get(database_path('seeders/sample-source.md'));

        $introduction = Document::factory()->for($users[0])->create([
            'document_namespace_id' => $typesafeNamespace->id,
            'path' => 'introduction',
            'title' => 'はじめに（TypeSafe AI ドキュメント日本語訳）',
            'content' => File::get(database_path('seeders/sample-translation.md')),
            'visibility' => DocumentVisibility::Public,
            'document_type' => DocumentType::Translation,
            'source_title' => 'Introduction',
            'source_url' => 'https://docs.typesafe.ai/introduction.md',
            'canonical_url' => 'https://docs.typesafe.ai/introduction',
            'source_author' => 'TypeSafe',
            'source_content' => $sourceContent,
        ]);

        $introductionSnapshot = $introduction->sourceSnapshots()->create([
            'content' => $sourceContent,
            'content_hash' => hash('sha256', $sourceContent),
            'title' => 'Introduction',
            'fetched_at' => now(),
        ]);

        $introduction->update(['document_source_snapshot_id' => $introductionSnapshot->id]);

        $quickstartSource = File::get(database_path('seeders/typesafe-quickstart-source.md'));

        $quickstart = Document::factory()->for($users[0])->create([
            'document_namespace_id' => $typesafeNamespace->id,
            'path' => 'introduction/quickstart',
            'title' => 'クイックスタート',
            'content' => File::get(database_path('seeders/typesafe-quickstart-translation.md')),
            'visibility' => DocumentVisibility::Public,
            'document_type' => DocumentType::Translation,
            'source_title' => 'Quick start',
            'source_url' => 'https://docs.typesafe.ai/introduction/quickstart.md',
            'canonical_url' => 'https://docs.typesafe.ai/introduction/quickstart',
            'source_author' => 'TypeSafe',
            'source_content' => $quickstartSource,
        ]);

        $quickstartSnapshot = $quickstart->sourceSnapshots()->create([
            'content' => $quickstartSource,
            'content_hash' => hash('sha256', $quickstartSource),
            'title' => 'Quick start',
            'fetched_at' => now(),
        ]);

        $quickstart->update(['document_source_snapshot_id' => $quickstartSnapshot->id]);
    }
}
