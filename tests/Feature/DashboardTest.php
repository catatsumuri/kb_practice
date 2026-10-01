<?php

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
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

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('documents', null)
            ->has('recentDocuments', 1)
            ->where('recentDocuments.0.id', $publicDocument->id)
            ->where('recentDocuments.0.title', '公開ドキュメント')
            ->where('recentDocuments.0.user.name', '公開ユーザー')
            ->has('standaloneDocuments', 1)
            ->where('standaloneCount', 1));
});

test('コレクションは閲覧可能な名前空間の公開文書だけを数える', function () {
    $user = User::factory()->create();
    $publicNamespace = DocumentNamespace::factory()->create(['is_public' => true, 'name' => 'A collection']);
    $ownedNamespace = DocumentNamespace::factory()->for($user, 'owner')->create(['name' => 'B collection']);
    $hiddenNamespace = DocumentNamespace::factory()->create();
    DocumentNamespace::factory()->create(['is_public' => true]);
    Document::factory()->for($publicNamespace, 'namespace')->create(['visibility' => DocumentVisibility::Public]);
    Document::factory()->for($publicNamespace, 'namespace')->create(['visibility' => DocumentVisibility::Private]);
    Document::factory()->for($ownedNamespace, 'namespace')->create(['visibility' => DocumentVisibility::Public]);
    $publicInPrivateNamespace = Document::factory()->for($hiddenNamespace, 'namespace')->create(['visibility' => DocumentVisibility::Public]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('collections', 2)
            ->where('collections.0.id', $publicNamespace->id)
            ->where('collections.0.documents_count', 1)
            ->where('collections.1.id', $ownedNamespace->id)
            ->where('collections.1.documents_count', 1)
            ->has('recentDocuments', 3)
            ->where('recentDocuments.0.id', $publicInPrivateNamespace->id)
            ->where('standaloneCount', 0));
});

test('最近の文書と単独文書は更新順で件数を制限する', function () {
    $this->freezeTime();
    $user = User::factory()->create();
    Document::factory()->for($user)->count(11)->create([
        'visibility' => DocumentVisibility::Public,
        'updated_at' => now()->subDay(),
    ]);
    $recentlyUpdated = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Public,
        'created_at' => now()->subYear(),
        'updated_at' => now(),
    ]);
    Document::factory()->for($user)->create(['visibility' => DocumentVisibility::Private]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentDocuments', 10)
            ->where('recentDocuments.0.id', $recentlyUpdated->id)
            ->has('standaloneDocuments', 6)
            ->where('standaloneDocuments.0.id', $recentlyUpdated->id)
            ->where('standaloneCount', 12));
});

test('公開文書はタイトルとパスで検索し非公開文書を除外する', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['is_public' => true]);
    $titleMatch = Document::factory()->for($namespace, 'namespace')->create([
        'title' => 'Python 入門', 'visibility' => DocumentVisibility::Public,
    ]);
    $pathMatch = Document::factory()->for($namespace, 'namespace')->create([
        'title' => 'クライアント', 'path' => 'sdk/python', 'visibility' => DocumentVisibility::Public,
    ]);
    Document::factory()->create(['title' => 'Python 秘密', 'visibility' => DocumentVisibility::Private]);
    Document::factory()->create(['title' => '無関係', 'visibility' => DocumentVisibility::Public]);

    $this->actingAs($user)->get(route('dashboard', ['q' => ' Python ']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.q', 'Python')
            ->where('filters.view', 'all')
            ->has('collections', 0)
            ->has('recentDocuments', 0)
            ->where('documents.total', 2)
            ->where('documents.data.0.id', $pathMatch->id)
            ->where('documents.data.1.id', $titleMatch->id)
            ->where('documents.data.0.namespace.name', $namespace->name));
});

test('公開文書一覧は検索語を保持してページ送りする', function () {
    $user = User::factory()->create();
    Document::factory()->for($user)->count(21)->create(['title' => 'Library item', 'visibility' => DocumentVisibility::Public]);
    Document::factory()->for($user)->create(['title' => 'Library secret', 'visibility' => DocumentVisibility::Private]);

    $this->actingAs($user)->get(route('dashboard', ['view' => 'all', 'q' => 'Library', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents.data', 1)
            ->where('documents.total', 21)
            ->where('documents.current_page', 2)
            ->where('documents.last_page', 2)
            ->where('documents.prev_page_url', fn (string $url) => str_contains($url, 'q=Library') && str_contains($url, 'view=all')));
});

test('単独文書一覧は名前空間付き文書を含めない', function () {
    $user = User::factory()->create();
    $standalone = Document::factory()->create(['visibility' => DocumentVisibility::Public]);
    Document::factory()->for(DocumentNamespace::factory(), 'namespace')->create(['visibility' => DocumentVisibility::Public]);

    $this->actingAs($user)->get(route('dashboard', ['view' => 'all', 'scope' => 'standalone']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('documents.total', 1)
            ->where('documents.data.0.id', $standalone->id)
            ->where('filters.scope', 'standalone'));
});

test('公開ライブラリーは文書がなくても表示できる', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('collections', 0)
            ->has('recentDocuments', 0)
            ->has('standaloneDocuments', 0)
            ->where('standaloneCount', 0));
});

test('検索結果がない場合は空の一覧を返す', function () {
    Document::factory()->create(['title' => '公開の文書', 'visibility' => DocumentVisibility::Public]);

    $this->actingAs(User::factory()->create())->get(route('dashboard', ['q' => "' OR 1=1 --"]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents.data', 0)
            ->where('documents.total', 0));
});

test('不正な検索条件を拒否する', function (array $query, string $field, string $message) {
    $this->actingAs(User::factory()->create())->get(route('dashboard', $query))
        ->assertSessionHasErrors([$field => $message]);
})->with([
    '検索語の型' => [['q' => ['invalid']], 'q', '検索語は文字列で入力してください。'],
    '検索語の長さ' => [['q' => str_repeat('a', 201)], 'q', '検索語は200文字以内で入力してください。'],
    '表示モード' => [['view' => 'invalid'], 'view', '表示モードが不正です。'],
    '絞り込み範囲' => [['scope' => 'invalid'], 'scope', '絞り込み範囲が不正です。'],
    'ページ番号の型' => [['page' => 'invalid'], 'page', 'ページ番号は整数で指定してください。'],
    'ページ番号の下限' => [['page' => 0], 'page', 'ページ番号は1以上で指定してください。'],
]);
