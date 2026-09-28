<?php

use App\Ai\Agents\TranslatorAgent;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('未認証ユーザーはドキュメントからログイン画面へリダイレクトされる', function () {
    $document = Document::factory()->create();

    $this->get(route('documents.index'))->assertRedirect(route('login'));
    $this->get(route('documents.show', $document))->assertRedirect(route('login'));
});

test('一覧には自分のドキュメントのみ表示される', function () {
    $user = User::factory()->create();
    $ownDocument = Document::factory()->for($user)->create();
    Document::factory()->create();

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/index')
            ->has('documents', 1)
            ->where('documents.0.id', $ownDocument->id));
});

test('一覧にはいいねの数が表示される', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();

    $likers = User::factory()->count(2)->create();
    foreach ($likers as $liker) {
        $document->likes()->create(['user_id' => $liker->id]);
    }

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/index')
            ->where('documents.0.likes_count', 2));
});

test('作成したドキュメントはログインユーザーに紐づく', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('documents.store', $namespace), [
            'title' => '自分のドキュメント',
            'content' => '本文',
            'visibility' => DocumentVisibility::Public->value,
        ])
        ->assertRedirect(route('namespaces.show', $namespace));

    $document = Document::query()->sole();

    expect($document->user->is($user))->toBeTrue()
        ->and($document->visibility)->toBe(DocumentVisibility::Public)
        ->and($document->document_namespace_id)->toBe($namespace->id);
});

test('記事作成時にスラッグを指定するとそのパスで表示できる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('documents.store', $namespace), [
            'title' => 'クイックスタート',
            'content' => '本文',
            'visibility' => DocumentVisibility::Public->value,
            'path' => 'quickstart',
        ])
        ->assertRedirect(route('namespaces.show', $namespace));

    $document = Document::query()->sole();

    expect($document->path)->toBe('quickstart');

    $this->get(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'quickstart']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->where('document.id', $document->id));
});

test('記事作成時のスラッグは省略できる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('documents.store', $namespace), [
            'title' => 'スラッグなし',
            'content' => '本文',
            'visibility' => DocumentVisibility::Public->value,
        ])
        ->assertRedirect(route('namespaces.show', $namespace));

    expect(Document::query()->sole()->path)->toBeNull();
});

test('同じネームスペース内で同じスラッグの記事は作成できない', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);
    Document::factory()->for($user)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'quickstart',
    ]);

    $this->actingAs($user)
        ->post(route('documents.store', $namespace), [
            'title' => '別の記事',
            'content' => '本文',
            'visibility' => DocumentVisibility::Public->value,
            'path' => 'quickstart',
        ])
        ->assertSessionHasErrors('path');

    expect(Document::query()->count())->toBe(1);
});

test('記事のスラッグに予約語は使用できない', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('documents.store', $namespace), [
            'title' => '記事',
            'content' => '本文',
            'visibility' => DocumentVisibility::Public->value,
            'path' => 'create',
        ])
        ->assertSessionHasErrors('path');

    expect(Document::query()->exists())->toBeFalse();
});

test('記事のスラッグの形式が不正な場合は作成できない', function (string $path) {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('documents.store', $namespace), [
            'title' => '記事',
            'content' => '本文',
            'visibility' => DocumentVisibility::Public->value,
            'path' => $path,
        ])
        ->assertSessionHasErrors('path');

    expect(Document::query()->exists())->toBeFalse();
})->with([
    'スペースを含む' => 'has spaces',
    '大文字を含む' => 'UPPERCASE',
    '先頭がハイフン' => '-leading-hyphen',
]);

test('他のユーザーのネームスペースには記事を作成できない', function () {
    $owner = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $owner->id]);
    $other = User::factory()->create();

    $this->actingAs($other);

    $this->get(route('documents.create', $namespace))->assertForbidden();

    $this->post(route('documents.store', $namespace), [
        'title' => '他人のネームスペースへの記事',
        'content' => '本文',
        'visibility' => DocumentVisibility::Public->value,
    ])->assertForbidden();

    expect(Document::query()->exists())->toBeFalse();
});

test('ネームスペースの所有者は記事の作成フォームを表示できる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('documents.create', $namespace))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/create')
            ->where('namespace.id', $namespace->id)
            ->where('namespace.slug', $namespace->slug));
});

test('ネームスペース配下のcreateパスは記事作成フォームとして解決される', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/documents/{$namespace->slug}/create")
        ->assertInertia(fn (Assert $page) => $page->component('documents/create'));
});

test('自分のドキュメントは表示できる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.id', $document->id)
            ->where('can.update', true)
            ->where('can.delete', true));
});

test('指定した公開範囲で作成されたドキュメントは詳細画面でも同じ公開範囲になる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Unlisted,
    ]);

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.visibility', DocumentVisibility::Unlisted->value));
});

test('指定した公開範囲に編集されたドキュメントは詳細画面でも同じ公開範囲になる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Private,
    ]);

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'title' => $document->title,
            'content' => $document->content,
            'visibility' => DocumentVisibility::Public->value,
        ])
        ->assertRedirect(route('documents.show', $document));

    $this->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.visibility', DocumentVisibility::Public->value));
});

test('自分のドキュメントは変更や削除ができる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'title' => '変更後のタイトル',
            'content' => '変更後の本文',
            'visibility' => DocumentVisibility::Unlisted->value,
        ])
        ->assertRedirect(route('documents.show', $document));

    expect($document->fresh())
        ->title->toBe('変更後のタイトル')
        ->content->toBe('変更後の本文')
        ->visibility->toBe(DocumentVisibility::Unlisted);

    $this->delete(route('documents.destroy', $document))
        ->assertRedirect(route('documents.index'));

    $this->assertModelMissing($document);
});

test('他のユーザーのドキュメントは表示や変更や削除ができない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'title' => '他のユーザーのドキュメント',
        'content' => '変更前の本文',
    ]);

    $this->actingAs($user);

    $this->get(route('documents.show', $document))->assertForbidden();
    $this->get(route('documents.edit', $document))->assertForbidden();
    $this->put(route('documents.update', $document), [
        'title' => '変更後のタイトル',
        'content' => '変更後の本文',
        'visibility' => DocumentVisibility::Public->value,
    ])->assertForbidden();
    $this->delete(route('documents.destroy', $document))->assertForbidden();

    expect($document->fresh())
        ->title->toBe('他のユーザーのドキュメント')
        ->content->toBe('変更前の本文');
});

test('他のユーザーの公開ドキュメントは表示できるが変更や削除はできない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'title' => '公開ドキュメント',
        'content' => '公開ドキュメントの本文',
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($user);

    $this->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.id', $document->id)
            ->where('document.title', '公開ドキュメント')
            ->where('document.content', '公開ドキュメントの本文')
            ->where('document.user.name', $document->user->name)
            ->where('can.update', false)
            ->where('can.delete', false));
    $this->get(route('documents.edit', $document))->assertForbidden();
    $this->put(route('documents.update', $document), [
        'title' => '変更後のタイトル',
        'content' => '変更後の本文',
        'visibility' => DocumentVisibility::Private->value,
    ])->assertForbidden();
    $this->delete(route('documents.destroy', $document))->assertForbidden();
});

test('ゲストは有効な共有リンクで限定公開ドキュメントを閲覧できる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Unlisted,
    ]);

    $shareUrl = URL::signedRoute('documents.shared', ['document' => $document]);

    $this->get($shareUrl)
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/shared')
            ->where('document.id', $document->id));
});

test('署名が不正な共有リンクではドキュメントを閲覧できない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Unlisted,
    ]);
    $otherDocument = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Unlisted,
    ]);

    $shareUrl = URL::signedRoute('documents.shared', ['document' => $document]);
    $tamperedUrl = str_replace(
        "documents/{$document->id}/shared",
        "documents/{$otherDocument->id}/shared",
        $shareUrl,
    );

    $this->get($tamperedUrl)->assertForbidden();
});

test('限定公開以外のドキュメントは共有リンクでは閲覧できない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Unlisted,
    ]);

    $shareUrl = URL::signedRoute('documents.shared', ['document' => $document]);

    $document->update(['visibility' => DocumentVisibility::Private]);

    $this->get($shareUrl)->assertNotFound();
});

test('限定公開ドキュメントの詳細画面には共有リンクが含まれる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Unlisted,
    ]);

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('shareUrl', URL::signedRoute('documents.shared', ['document' => $document])));
});

test('非公開・公開ドキュメントの詳細画面には共有リンクが含まれない', function () {
    $user = User::factory()->create();

    $privateDocument = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Private,
    ]);
    $publicDocument = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($user);

    $this->get(route('documents.show', $privateDocument))
        ->assertInertia(fn (Assert $page) => $page->where('shareUrl', null));
    $this->get(route('documents.show', $publicDocument))
        ->assertInertia(fn (Assert $page) => $page->where('shareUrl', null));
});

test('他のログインユーザーは限定公開ドキュメントを通常の詳細画面からは閲覧できない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Unlisted,
    ]);

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertForbidden();
});

test('公開範囲には定義済みの値だけを指定できる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'title' => '変更後のタイトル',
            'content' => '変更後の本文',
            'visibility' => 'invalid',
        ])
        ->assertSessionHasErrors('visibility');

    expect($document->fresh()->visibility)->toBe(DocumentVisibility::Private);
});

test('原文があるドキュメントはAI翻訳結果で本文が上書きされる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'content' => '元の本文',
        'source_content' => 'Original text',
    ]);

    TranslatorAgent::fake(['翻訳された本文']);

    $this->actingAs($user)
        ->post(route('documents.translate', $document))
        ->assertRedirect(route('documents.edit', $document));

    TranslatorAgent::assertPrompted('Original text');
    expect($document->fresh()->content)->toBe('翻訳された本文');
});

test('原文がないドキュメントはAI翻訳できない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'content' => '元の本文',
        'source_content' => null,
    ]);

    TranslatorAgent::fake();

    $this->actingAs($user)
        ->post(route('documents.translate', $document))
        ->assertStatus(422);

    TranslatorAgent::assertNeverPrompted();
    expect($document->fresh()->content)->toBe('元の本文');
});

test('他のユーザーのドキュメントはAI翻訳できない', function () {
    $owner = User::factory()->create();
    $document = Document::factory()->for($owner)->create([
        'source_content' => 'Original text',
    ]);

    $other = User::factory()->create();

    TranslatorAgent::fake();

    $this->actingAs($other)
        ->post(route('documents.translate', $document))
        ->assertForbidden();

    TranslatorAgent::assertNeverPrompted();
});

test('ネームスペースとパスを指定してドキュメントを表示できる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);
    $document = Document::factory()->for($user)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction',
    ]);

    $this->actingAs($user)
        ->get(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'introduction']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.id', $document->id));
});

test('複数セグメントのパスを持つドキュメントもパス指定URLで表示できる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);
    $document = Document::factory()->for($user)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction/quickstart',
    ]);

    $this->actingAs($user)
        ->get(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'introduction/quickstart']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.id', $document->id));
});

test('ネームスペース内に存在しないパスは404になる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);
    Document::factory()->for($user)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction',
    ]);

    $this->actingAs($user)
        ->get(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'missing']))
        ->assertNotFound();
});

test('パス指定URLでも他のユーザーの非公開ドキュメントは表示できない', function () {
    $owner = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $owner->id]);
    Document::factory()->for($owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction',
        'visibility' => DocumentVisibility::Private,
    ]);

    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'introduction']))
        ->assertForbidden();
});

test('数値のドキュメントIDのURLは通常のdocuments.showで解決される', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();

    $this->actingAs($user)
        ->get("/documents/{$document->id}")
        ->assertInertia(fn (Assert $page) => $page->component('documents/show'));
});

test('単一セグメントのネームスペーススラッグのURLはnamespaces.showで解決される', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/documents/{$namespace->slug}")
        ->assertInertia(fn (Assert $page) => $page->component('namespaces/show'));
});
