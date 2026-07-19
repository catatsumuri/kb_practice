<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Document titles and source filenames per syntax guide locale, keyed
     * by the directory name under database/seeders/syntax-guide/.
     *
     * @var array<string, array<string, string>>
     */
    private const SYNTAX_GUIDE_DOCUMENTS = [
        'en' => [
            'Markdown Syntax Guide' => 'index.md',
            'Extended Markdown Syntax' => 'extended.md',
            'Zenn Syntax' => 'zenn.md',
            'Mintlify Syntax' => 'mintlify.md',
            'Thinkstream Syntax' => 'thinkstream.md',
        ],
        'ja' => [
            'Markdown 構文ガイド' => 'index.md',
            '拡張 Markdown 構文' => 'extended.md',
            'Zenn 構文' => 'zenn.md',
            'Mintlify 構文' => 'mintlify.md',
            'Thinkstream 構文' => 'thinkstream.md',
        ],
    ];

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

        $locale = $this->resolveSyntaxGuideLocale();

        foreach (self::SYNTAX_GUIDE_DOCUMENTS[$locale] as $title => $filename) {
            Document::factory()->for($testUser)->create([
                'title' => $title,
                'content' => File::get(database_path("seeders/syntax-guide/{$locale}/{$filename}")),
            ]);
        }
    }

    /**
     * Picks the syntax guide locale directory: the app locale
     * (APP_LOCALE) if a translation exists, otherwise the fallback
     * locale (APP_FALLBACK_LOCALE), otherwise 'en'.
     */
    private function resolveSyntaxGuideLocale(): string
    {
        foreach ([App::getLocale(), App::getFallbackLocale(), 'en'] as $locale) {
            if (array_key_exists($locale, self::SYNTAX_GUIDE_DOCUMENTS)) {
                return $locale;
            }
        }

        throw new RuntimeException('No syntax guide documents available for any configured locale.');
    }
}
