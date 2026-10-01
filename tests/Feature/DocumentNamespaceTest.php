<?php

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('未ログインで公開名前空間のルートを開くと設定した公開記事へ転送する', function () {
    $namespace = DocumentNamespace::factory()->create([
        'is_public' => true,
        'guest_redirect_path' => 'introduction/quickstart',
    ]);
    Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction/quickstart',
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->get(route('namespaces.show', $namespace))
        ->assertRedirect(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'introduction/quickstart']));
});

test('ログイン中は転送先が設定されていても一覧を表示する', function (bool $isOwner) {
    $namespace = DocumentNamespace::factory()->create(['is_public' => true, 'guest_redirect_path' => 'intro']);
    Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'intro',
        'visibility' => DocumentVisibility::Public,
    ]);
    $user = $isOwner ? $namespace->owner : User::factory()->create();

    $this->actingAs($user)->get(route('namespaces.show', $namespace))
        ->assertInertia(fn (Assert $page) => $page->component('namespaces/show')->has('documents', 1));
})->with(['所有者' => true, '他のログインユーザー' => false]);

test('転送先が未設定または公開記事でなくなった場合は一覧を表示する', function (?string $path, bool $createPrivateDocument) {
    $namespace = DocumentNamespace::factory()->create(['is_public' => true, 'guest_redirect_path' => $path]);
    if ($createPrivateDocument) {
        Document::factory()->for($namespace->owner)->create([
            'document_namespace_id' => $namespace->id,
            'path' => $path,
            'visibility' => DocumentVisibility::Private,
        ]);
    }

    $this->get(route('namespaces.show', $namespace))
        ->assertInertia(fn (Assert $page) => $page->component('namespaces/show')->has('documents', 0));
})->with(['未設定' => [null, false], '削除された記事' => ['deleted', false], '非公開化された記事' => ['private', true]]);

test('非公開名前空間は転送先を設定しても未ログインには公開しない', function () {
    $namespace = DocumentNamespace::factory()->create(['is_public' => false, 'guest_redirect_path' => 'intro']);
    Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'intro',
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->get(route('namespaces.show', $namespace))->assertForbidden();
});

test('所有者は公開記事の転送先を設定し空欄で解除できる', function (?string $path) {
    $namespace = DocumentNamespace::factory()->create(['guest_redirect_path' => 'intro']);
    Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'intro',
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($namespace->owner)->put(route('namespaces.update', $namespace), [
        'name' => $namespace->name,
        'guest_redirect_path' => $path,
    ])->assertRedirect(route('namespaces.show', $namespace));

    expect($namespace->fresh()->guest_redirect_path)->toBe($path === '' ? null : $path);
})->with(['設定' => 'intro', '解除' => '', 'nullで解除' => [null]]);

test('他の名前空間の転送先と非公開記事と外部URLは設定できない', function (string $path) {
    $namespace = DocumentNamespace::factory()->create();
    Document::factory()->for($namespace->owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'private',
        'visibility' => DocumentVisibility::Private,
    ]);
    $other = DocumentNamespace::factory()->create();
    Document::factory()->for($other->owner)->create([
        'document_namespace_id' => $other->id,
        'path' => 'other',
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($namespace->owner)->put(route('namespaces.update', $namespace), [
        'name' => $namespace->name,
        'guest_redirect_path' => $path,
    ])->assertSessionHasErrors(['guest_redirect_path' => 'この名前空間の公開記事のパスを指定してください。']);

    expect($namespace->fresh()->guest_redirect_path)->toBeNull();
})->with(['private', 'other', 'missing', 'https://example.com']);

test('所有者以外は転送先を変更できない', function () {
    $namespace = DocumentNamespace::factory()->create(['is_public' => true]);

    $this->actingAs(User::factory()->create())->put(route('namespaces.update', $namespace), [
        'name' => $namespace->name,
        'guest_redirect_path' => 'intro',
    ])->assertForbidden();

    expect($namespace->fresh()->guest_redirect_path)->toBeNull();
});

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
        ->and($namespace->source_url)->toBe('https://docs.typesafe.ai')
        ->and($namespace->is_public)->toBeFalse();
});

test('公開を指定してネームスペースを作成できる', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('namespaces.store'), [
            'slug' => 'typesafe',
            'name' => 'Typesafe Docs',
            'is_public' => true,
        ])
        ->assertRedirect(route('documents.index'));

    expect(DocumentNamespace::query()->sole()->is_public)->toBeTrue();
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

test('未認証ユーザーは非公開ネームスペースの詳細を閲覧できない', function () {
    $namespace = DocumentNamespace::factory()->create(['is_public' => false]);

    $this->get(route('namespaces.show', $namespace))->assertForbidden();
});

test('未認証ユーザーは公開ネームスペースの詳細を閲覧できる', function () {
    $owner = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create([
        'owner_user_id' => $owner->id,
        'is_public' => true,
    ]);
    $publicDocument = Document::factory()->for($owner)->create([
        'document_namespace_id' => $namespace->id,
        'visibility' => DocumentVisibility::Public,
    ]);
    Document::factory()->for($owner)->create([
        'document_namespace_id' => $namespace->id,
        'visibility' => DocumentVisibility::Private,
    ]);

    $this->get(route('namespaces.show', $namespace))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('namespaces/show')
            ->where('namespace.id', $namespace->id)
            ->has('documents', 1)
            ->where('documents.0.id', $publicDocument->id));
});

test('所有者はネームスペースの公開設定を編集できる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create([
        'owner_user_id' => $user->id,
        'name' => '元の名前',
        'is_public' => false,
    ]);

    $this->actingAs($user)
        ->put(route('namespaces.update', $namespace), [
            'name' => '新しい名前',
            'is_public' => true,
        ])
        ->assertRedirect(route('namespaces.show', $namespace));

    expect($namespace->fresh())
        ->name->toBe('新しい名前')
        ->is_public->toBeTrue();
});

test('ネームスペースのスラッグは編集で変更されない', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create([
        'owner_user_id' => $user->id,
        'slug' => 'typesafe',
    ]);

    $this->actingAs($user)
        ->put(route('namespaces.update', $namespace), [
            'slug' => 'changed',
            'name' => $namespace->name,
        ])
        ->assertRedirect(route('namespaces.show', $namespace));

    expect($namespace->fresh()->slug)->toBe('typesafe');
});

test('他のユーザーはネームスペースの編集画面を開けず更新もできない', function () {
    $namespace = DocumentNamespace::factory()->create(['is_public' => false]);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('namespaces.edit', $namespace))
        ->assertForbidden();

    $this->actingAs($other)
        ->put(route('namespaces.update', $namespace), [
            'name' => '乗っ取り',
            'is_public' => true,
        ])
        ->assertForbidden();

    expect($namespace->fresh())
        ->name->not->toBe('乗っ取り')
        ->is_public->toBeFalse();
});

test('未認証ユーザーはネームスペースの編集画面からログイン画面へリダイレクトされる', function () {
    $namespace = DocumentNamespace::factory()->create(['is_public' => true]);

    $this->get(route('namespaces.edit', $namespace))->assertRedirect(route('login'));
});

test('従来のナビゲーション定義に沿ってツリー化し、未掲載の文書は末尾の無題ノードに入る', function () {
    $namespace = DocumentNamespace::factory()->create([
        'navigation' => [
            ['title' => 'Start', 'pages' => ['b', 'missing', 'a']],
            ['title' => 'Empty', 'pages' => ['missing']],
        ],
    ]);
    $documents = collect(['a', 'b', 'z'])->map(
        fn (string $path) => Document::factory()->for(User::factory()->create())->create([
            'document_namespace_id' => $namespace->id,
            'path' => $path,
        ]),
    );

    $tree = $namespace->navigationTree($documents);

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['title'])->toBe('Start')
        ->and(collect($tree[0]['children'])->pluck('document.path')->all())->toBe(['b', 'a'])
        ->and($tree[1]['title'])->toBeNull()
        ->and(collect($tree[1]['children'])->pluck('document.path')->all())->toBe(['z']);
});

test('ナビゲーションがない名前空間は全ての文書を1つの無題ノードにする', function () {
    $namespace = DocumentNamespace::factory()->create(['navigation' => null]);
    $documents = Document::factory()->count(2)->for(User::factory()->create())->create([
        'document_namespace_id' => $namespace->id,
    ]);

    $tree = $namespace->navigationTree($documents);

    expect($tree)->toHaveCount(1)->and($tree[0]['title'])->toBeNull()
        ->and($tree[0]['children'])->toHaveCount(2);
});

test('ナビゲーションはリンク付き親ノード、入れ子、ラベルを再帰的に解決する', function () {
    $namespace = DocumentNamespace::factory()->create([
        'navigation' => [[
            'title' => 'Cookbooks',
            'page' => 'cookbooks',
            'pages' => [[
                'title' => 'Self-consistency',
                'pages' => [
                    ['page' => 'cookbooks/nouls', 'label' => 'Beginner'],
                    ['page' => 'cookbooks/choices', 'title' => 'Choices', 'label' => 'Intermediate'],
                    'missing',
                ],
            ]],
        ]],
    ]);
    $documents = collect(['cookbooks', 'cookbooks/nouls', 'cookbooks/choices'])->map(
        fn (string $path) => Document::factory()->for(User::factory()->create())->create([
            'document_namespace_id' => $namespace->id,
            'path' => $path,
            'title' => $path,
        ]),
    );

    $tree = $namespace->navigationTree($documents);

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['title'])->toBe('Cookbooks')
        ->and($tree[0]['document']->path)->toBe('cookbooks')
        ->and($tree[0]['children'][0]['title'])->toBe('Self-consistency')
        ->and($tree[0]['children'][0]['children'][0]['document']->path)->toBe('cookbooks/nouls')
        ->and($tree[0]['children'][0]['children'][0]['label'])->toBe('Beginner')
        ->and($tree[0]['children'][0]['children'][1]['title'])->toBe('Choices')
        ->and($tree[0]['children'][0]['children'][1]['label'])->toBe('Intermediate')
        ->and($namespace->navigationDocuments($documents)->pluck('path')->all())->toBe([
            'cookbooks',
            'cookbooks/nouls',
            'cookbooks/choices',
        ]);
});

test('文書ページはサイドバー用にナビゲーションのツリーと順序を渡す', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create([
        'slug' => 'typesafe',
        'is_public' => true,
        'navigation' => [['title' => 'Start', 'pages' => ['second', 'first']]],
    ]);
    foreach (['first', 'second'] as $path) {
        Document::factory()->for($user)->create([
            'document_namespace_id' => $namespace->id,
            'path' => $path,
            'title' => $path === 'first' ? 'あ' : 'い',
            'visibility' => DocumentVisibility::Public,
        ]);
    }

    $this->get(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'first']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('namespaceNavigation.0.title', 'Start')
            ->where('namespaceNavigation.0.children.0.document.path', 'second')
            ->where('namespaceNavigation.0.children.1.document.path', 'first'));
});

test('名前空間の一覧はナビゲーションの順序で並ぶ', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create([
        'owner_user_id' => $user->id,
        'is_public' => true,
        'navigation' => [['title' => 'Start', 'pages' => ['second', 'first']]],
    ]);
    foreach (['first', 'second'] as $path) {
        Document::factory()->for($user)->create([
            'document_namespace_id' => $namespace->id,
            'path' => $path,
            'visibility' => DocumentVisibility::Public,
        ]);
    }

    $this->get(route('namespaces.show', $namespace))
        ->assertInertia(fn (Assert $page) => $page
            ->where('documents.0.path', 'second')
            ->where('documents.1.path', 'first'));
});
