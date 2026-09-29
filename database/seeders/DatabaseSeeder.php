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
            'navigation' => [
                [
                    'title' => 'スタートガイド',
                    'pages' => [
                        'introduction',
                        'introduction/quickstart',
                        'introduction/coding-agents',
                        'concepts/use-case-map',
                    ],
                ],
                [
                    'title' => 'コンセプト',
                    'pages' => [
                        'concepts/system-one',
                        'concepts/state',
                        'confidence',
                        'concepts/how-to-build-with-system-one',
                        'introduction/machine-learning-primer',
                    ],
                ],
                [
                    'title' => 'パターン',
                    'pages' => [
                        'patterns',
                        'patterns/fan-out',
                        'patterns/confidence-routing',
                        'patterns/composite-scoring',
                        'patterns/intent-routing',
                    ],
                ],
            ],
            'is_public' => true,
        ]);

        $this->createTranslation(
            $users[0],
            $typesafeNamespace,
            path: 'introduction',
            sourceTitle: 'Introduction',
            title: 'はじめに（TypeSafe AI ドキュメント日本語訳）',
            sourceContent: File::get(database_path('seeders/sample-source.md')),
            content: File::get(database_path('seeders/sample-translation.md')),
        );

        $this->createTranslation(
            $users[0],
            $typesafeNamespace,
            path: 'introduction/quickstart',
            sourceTitle: 'Quick start',
            title: 'クイックスタート',
            sourceContent: File::get(database_path('seeders/typesafe-quickstart-source.md')),
            content: File::get(database_path('seeders/typesafe-quickstart-translation.md')),
        );

        $this->createTranslation(
            $users[0],
            $typesafeNamespace,
            path: 'introduction/coding-agents',
            sourceTitle: 'Jev with coding agents',
            title: 'コーディングエージェントとJev',
            sourceContent: File::get(database_path('seeders/typesafe-coding-agents-source.md')),
            content: File::get(database_path('seeders/typesafe-coding-agents-translation.md')),
        );
    }

    /**
     * Create a public translation document mirroring a page of the
     * namespace's source site, with its source snapshot adopted.
     */
    private function createTranslation(
        User $user,
        DocumentNamespace $namespace,
        string $path,
        string $sourceTitle,
        string $title,
        string $sourceContent,
        string $content,
    ): Document {
        $document = Document::factory()->for($user)->create([
            'document_namespace_id' => $namespace->id,
            'path' => $path,
            'title' => $title,
            'content' => $content,
            'visibility' => DocumentVisibility::Public,
            'document_type' => DocumentType::Translation,
            'source_title' => $sourceTitle,
            'source_url' => "{$namespace->source_url}/{$path}.md",
            'canonical_url' => "{$namespace->source_url}/{$path}",
            'source_author' => 'TypeSafe',
            'source_content' => $sourceContent,
        ]);

        $snapshot = $document->sourceSnapshots()->create([
            'content' => $sourceContent,
            'content_hash' => hash('sha256', $sourceContent),
            'title' => $sourceTitle,
            'fetched_at' => now(),
        ]);

        $document->update(['document_source_snapshot_id' => $snapshot->id]);

        return $document;
    }
}
