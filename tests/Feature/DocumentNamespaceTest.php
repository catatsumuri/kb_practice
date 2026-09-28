<?php

use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('未認証ユーザーはネームスペースからログイン画面へリダイレクトされる', function () {
    $this->get(route('namespaces.create'))->assertRedirect(route('login'));
});

test('一覧には自分のネームスペースのみ表示される', function () {
    $user = User::factory()->create();
    $ownNamespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);
    DocumentNamespace::factory()->create();

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/index')
            ->has('namespaces', 1)
            ->where('namespaces.0.id', $ownNamespace->id));
});

test('ネームスペースを作成するとログインユーザーが所有者になる', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('namespaces.store'), [
            'slug' => 'typesafe',
            'name' => 'Typesafe Docs',
            'source_url' => 'https://docs.typesafe.ai',
        ])
        ->assertRedirect(route('documents.index'));

    $namespace = DocumentNamespace::query()->sole();

    expect($namespace->owner_user_id)->toBe($user->id)
        ->and($namespace->slug)->toBe('typesafe')
        ->and($namespace->name)->toBe('Typesafe Docs')
        ->and($namespace->source_url)->toBe('https://docs.typesafe.ai');
});

test('同じスラッグのネームスペースは作成できない', function () {
    $user = User::factory()->create();
    DocumentNamespace::factory()->create(['slug' => 'typesafe']);

    $this->actingAs($user)
        ->post(route('namespaces.store'), [
            'slug' => 'typesafe',
            'name' => '別のネームスペース',
        ])
        ->assertSessionHasErrors('slug');

    expect(DocumentNamespace::query()->count())->toBe(1);
});

test('予約語のスラッグではネームスペースを作成できない', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('namespaces.store'), [
            'slug' => 'documents',
            'name' => 'テストネームスペース',
        ])
        ->assertSessionHasErrors('slug');

    expect(DocumentNamespace::query()->exists())->toBeFalse();
});

test('不正な形式のスラッグではネームスペースを作成できない', function (string $slug) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('namespaces.store'), [
            'slug' => $slug,
            'name' => 'テストネームスペース',
        ])
        ->assertSessionHasErrors('slug');

    expect(DocumentNamespace::query()->exists())->toBeFalse();
})->with([
    'スペースを含む' => 'has spaces',
    '大文字を含む' => 'UPPERCASE',
    '先頭がハイフン' => '-leading-hyphen',
    '数字のみ' => '12345',
]);

test('名前が未入力の場合はネームスペースを作成できない', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('namespaces.store'), [
            'slug' => 'typesafe',
        ])
        ->assertSessionHasErrors('name');

    expect(DocumentNamespace::query()->exists())->toBeFalse();
});

test('元サイトURLは省略できる', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('namespaces.store'), [
            'slug' => 'typesafe',
            'name' => 'Typesafe Docs',
        ])
        ->assertRedirect(route('documents.index'));

    expect(DocumentNamespace::query()->sole()->source_url)->toBeNull();
});

test('元サイトURLが不正な形式の場合はネームスペースを作成できない', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('namespaces.store'), [
            'slug' => 'typesafe',
            'name' => 'Typesafe Docs',
            'source_url' => 'not-a-url',
        ])
        ->assertSessionHasErrors('source_url');

    expect(DocumentNamespace::query()->exists())->toBeFalse();
});

test('所有者はネームスペースの詳細を表示できる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);
    $ownDocument = Document::factory()->for($user)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction',
    ]);

    $otherNamespace = DocumentNamespace::factory()->create();
    Document::factory()->create(['document_namespace_id' => $otherNamespace->id]);

    $this->actingAs($user)
        ->get(route('namespaces.show', $namespace))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('namespaces/show')
            ->where('namespace.id', $namespace->id)
            ->has('documents', 1)
            ->where('documents.0.id', $ownDocument->id)
            ->where('documents.0.path', 'introduction'));
});

test('他のユーザーのネームスペースの詳細は表示できない', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create();

    $this->actingAs($user)
        ->get(route('namespaces.show', $namespace))
        ->assertForbidden();
});

test('未認証ユーザーはネームスペースの詳細からログイン画面へリダイレクトされる', function () {
    $namespace = DocumentNamespace::factory()->create();

    $this->get(route('namespaces.show', $namespace))->assertRedirect(route('login'));
});
