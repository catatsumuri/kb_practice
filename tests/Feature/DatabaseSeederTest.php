<?php

use App\Models\Document;
use Database\Seeders\DatabaseSeeder;

test('シーダーはサンプルドキュメントを作成する', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Document::query()->pluck('title')->all())->toBe([
        'Markdownでドキュメントを書く',
        'Mintlifyコンポーネント記法 (inkstream2)',
        'Markdown Syntax Guide',
        'Extended Markdown Syntax',
        'Zenn Syntax',
        'Mintlify Syntax',
        'Thinkstream Syntax',
    ]);

    $mintlifyDocument = Document::query()
        ->where('title', 'Mintlifyコンポーネント記法 (inkstream2)')
        ->sole();

    expect($mintlifyDocument->content)->toContain('<Steps>');

    $syntaxGuide = Document::query()
        ->where('title', 'Mintlify Syntax')
        ->sole();

    expect($syntaxGuide->content)
        ->toContain('<CardGroup cols={2}>')
        ->toContain('<Tree>');
});
