<?php

use App\Models\Document;
use Database\Seeders\DatabaseSeeder;

test('シーダーはサンプルドキュメントを作成する', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Document::query()->pluck('title')->all())->toBe([
        'Markdownでドキュメントを書く',
        'Mintlifyコンポーネント記法 (inkstream2)',
    ]);

    $mintlifyDocument = Document::query()
        ->where('title', 'Mintlifyコンポーネント記法 (inkstream2)')
        ->sole();

    expect($mintlifyDocument->content)->toContain('<Steps>');
});
