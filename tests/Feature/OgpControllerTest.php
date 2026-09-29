<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('OGPメタデータを取得できる', function () {
    Http::fake([
        'example.com/*' => Http::response(<<<'HTML'
            <html><head>
                <meta property="og:title" content="Example Title">
                <meta property="og:description" content="Example Description">
                <meta property="og:image" content="https://example.com/image.png">
            </head></html>
            HTML),
    ]);

    $this->getJson(route('documents.ogp', ['url' => 'https://example.com/article']))
        ->assertOk()
        ->assertJson([
            'title' => 'Example Title',
            'description' => 'Example Description',
            'image' => 'https://example.com/image.png',
        ]);
});

test('OGPメタデータの取得結果はキャッシュされる', function () {
    Http::fake([
        'example.com/*' => Http::response('<html><head><meta property="og:title" content="Cached"></head></html>'),
    ]);

    $url = route('documents.ogp', ['url' => 'https://example.com/cached']);

    $this->getJson($url)->assertOk();
    $this->getJson($url)->assertOk();

    Http::assertSentCount(1);
});

test('URLを取得できない場合は502を返す', function () {
    Http::fake([
        'example.com/*' => Http::response('', 500),
    ]);

    $this->getJson(route('documents.ogp', ['url' => 'https://example.com/broken']))
        ->assertStatus(502)
        ->assertJson(['title' => null, 'description' => null, 'image' => null]);

    expect(Cache::has('ogp:'.md5('https://example.com/broken')))->toBeFalse();
});

test('URLが不正な場合は422を返す', function () {
    $this->getJson(route('documents.ogp', ['url' => 'not-a-url']))
        ->assertStatus(422)
        ->assertJson(['title' => null, 'description' => null, 'image' => null]);
});

test('プライベートIPに解決されるURLは拒否される', function () {
    Http::fake();

    $this->getJson(route('documents.ogp', ['url' => 'http://127.0.0.1/internal']))
        ->assertStatus(422)
        ->assertJson(['title' => null, 'description' => null, 'image' => null]);

    Http::assertNothingSent();
});
