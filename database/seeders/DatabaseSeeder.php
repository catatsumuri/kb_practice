<?php

namespace Database\Seeders;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Sequence;
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

        $documentContent = File::get(database_path('seeders/sample-document.md'));
        $visibilities = DocumentVisibility::cases();
        $visibilityLabels = [
            DocumentVisibility::Private->value => '非公開',
            DocumentVisibility::Public->value => '公開',
            DocumentVisibility::Unlisted->value => '限定公開',
        ];

        foreach ($users as $user) {
            Document::factory()
                ->count(10)
                ->for($user)
                ->sequence(fn (Sequence $sequence): array => [
                    'title' => sprintf(
                        'Markdownでドキュメントを書く %02d（%s）',
                        $sequence->index + 1,
                        $visibilityLabels[$visibilities[$sequence->index % count($visibilities)]->value],
                    ),
                    'content' => $documentContent,
                    'visibility' => $visibilities[$sequence->index % count($visibilities)],
                ])
                ->create();
        }
    }
}
