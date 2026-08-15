<?php

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('未認証ユーザーはダッシュボードからログイン画面へリダイレクトされる', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('認証済みユーザーはダッシュボードを表示できる', function () {
    $user = User::factory()->create();
    $author = User::factory()->create(['name' => '公開ユーザー']);
    $publicDocument = Document::factory()->for($author)->create([
        'title' => '公開ドキュメント',
        'visibility' => DocumentVisibility::Public,
    ]);
    Document::factory()->for($author)->create([
        'title' => '非公開ドキュメント',
        'visibility' => DocumentVisibility::Private,
    ]);
    Document::factory()->for($author)->create([
        'title' => '限定公開ドキュメント',
        'visibility' => DocumentVisibility::Unlisted,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('documents', 1)
            ->where('documents.0.id', $publicDocument->id)
            ->where('documents.0.title', '公開ドキュメント')
            ->where('documents.0.user.name', '公開ユーザー'));
});
