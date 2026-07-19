<?php

use App\Models\Document;
use Database\Seeders\DatabaseSeeder;

test('シーダーは APP_LOCALE のドキュメントを作成する', function () {
    config(['app.locale' => 'en']);

    $this->seed(DatabaseSeeder::class);

    expect(Document::query()->pluck('title')->all())->toBe([
        'Markdown Syntax Guide',
        'Extended Markdown Syntax',
        'Zenn Syntax',
        'Mintlify Syntax',
        'Thinkstream Syntax',
    ]);

    $syntaxGuide = Document::query()
        ->where('title', 'Mintlify Syntax')
        ->sole();

    expect($syntaxGuide->content)
        ->toContain('<CardGroup cols={2}>')
        ->toContain('<Tree>');
});

test('シーダーは APP_LOCALE=ja のとき日本語ドキュメントを作成する', function () {
    config(['app.locale' => 'ja']);

    $this->seed(DatabaseSeeder::class);

    expect(Document::query()->pluck('title')->all())->toBe([
        'Markdown 構文ガイド',
        '拡張 Markdown 構文',
        'Zenn 構文',
        'Mintlify 構文',
        'Thinkstream 構文',
    ]);

    $syntaxGuide = Document::query()
        ->where('title', 'Mintlify 構文')
        ->sole();

    expect($syntaxGuide->content)
        ->toContain('<CardGroup cols={2}>')
        ->toContain('<Tree>');
});

test('シーダーは未知の APP_LOCALE のとき APP_FALLBACK_LOCALE のドキュメントを作成する', function () {
    config(['app.locale' => 'fr', 'app.fallback_locale' => 'en']);

    $this->seed(DatabaseSeeder::class);

    expect(Document::query()->pluck('title')->all())->toBe([
        'Markdown Syntax Guide',
        'Extended Markdown Syntax',
        'Zenn Syntax',
        'Mintlify Syntax',
        'Thinkstream Syntax',
    ]);
});
