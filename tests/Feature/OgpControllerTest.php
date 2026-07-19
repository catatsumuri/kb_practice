<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('未認証ユーザーはアクセスできない', function () {
    $this->getJson('/api/ogp?url='.urlencode('https://example.com'))
        ->assertUnauthorized();
});

test('invalid URL returns 422', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/ogp?url=not-a-url')
        ->assertUnprocessable()
        ->assertJson(['error' => 'Invalid URL']);
});

test('url parameter is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/ogp')
        ->assertUnprocessable()
        ->assertJson(['error' => 'Invalid URL']);
});

test('private network URLs are rejected', function () {
    $user = User::factory()->create();

    Http::fake();

    $this->actingAs($user)
        ->getJson('/api/ogp?url='.urlencode('http://127.0.0.1/private'))
        ->assertUnprocessable()
        ->assertJson(['error' => 'Invalid URL']);

    Http::assertNothingSent();
});

test('returns 200 with metadata when OGP fetch succeeds', function () {
    $user = User::factory()->create();

    Http::fake([
        'example.com' => Http::response(<<<'HTML'
            <html>
            <head>
                <meta property="og:title" content="Example Site">
                <meta property="og:description" content="An example website.">
                <meta property="og:image" content="https://example.com/og.png">
            </head>
            </html>
            HTML),
    ]);

    $this->actingAs($user)
        ->getJson('/api/ogp?url='.urlencode('https://example.com'))
        ->assertSuccessful()
        ->assertJson([
            'title' => 'Example Site',
            'description' => 'An example website.',
            'image' => 'https://example.com/og.png',
            'url' => 'https://example.com',
        ]);
});

test('returns 404 when the page has no title', function () {
    $user = User::factory()->create();

    Http::fake([
        'example.com' => Http::response('<html><head></head></html>'),
    ]);

    $this->actingAs($user)
        ->getJson('/api/ogp?url='.urlencode('https://example.com'))
        ->assertNotFound()
        ->assertJson(['error' => 'Failed to fetch OGP metadata']);
});

test('result is cached for 24 hours', function () {
    $user = User::factory()->create();
    $url = 'https://example.com';

    Http::fake([
        'example.com' => Http::response(
            '<html><head><title>Cached Site</title></head></html>',
        ),
    ]);

    Cache::forget('ogp:'.md5($url));

    $this->actingAs($user)->getJson('/api/ogp?url='.urlencode($url))->assertStatus(200);
    $this->actingAs($user)->getJson('/api/ogp?url='.urlencode($url))->assertStatus(200);

    Http::assertSentCount(1);
});
