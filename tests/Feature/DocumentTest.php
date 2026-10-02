<?php

use App\Ai\Agents\TranslatorAgent;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\DocumentRevision;
use App\Models\DocumentSourceSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('未認証ユーザーは一覧からログイン画面へリダイレクトされる', function () {
    $this->get(route('documents.index'))->assertRedirect(route('login'));
});

test('未認証ユーザーは非公開ドキュメントを閲覧できない', function () {
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Private,
    ]);

    $this->get(route('documents.show', $document))->assertForbidden();
});

test('未認証ユーザーは公開ドキュメントを閲覧できる', function () {
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.id', $document->id));
});

test('一覧はネームスペースだけを渡し、ドキュメント単体は渡さない', function () {
    $user = User::factory()->create();
    Document::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/index')
            ->has('namespaces')
            ->missing('documents'));
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

test('本文が空でもドキュメントを作成できる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('documents.store', $namespace), [
            'title' => '空のドキュメント',
            'content' => '',
            'visibility' => DocumentVisibility::Public->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('namespaces.show', $namespace));

    expect(Document::query()->sole()->content)->toBe('');
});

test('本文を空にして更新でき、直前の本文はリビジョンに残る', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create(['title' => '記事', 'content' => '変更前の本文']);

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'title' => '記事',
            'content' => '',
            'visibility' => $document->visibility->value,
        ])
        ->assertSessionHasNoErrors();

    expect($document->fresh()->content)->toBe('')
        ->and($document->revisions()->sole()->content)->toBe('変更前の本文');
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

test('記事作成時に複数階層のスラッグを指定するとそのパスで表示できる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('documents.store', $namespace), [
            'title' => 'System One モデル',
            'content' => '本文',
            'visibility' => DocumentVisibility::Public->value,
            'path' => 'concepts/system-one',
        ])
        ->assertRedirect(route('namespaces.show', $namespace));

    $document = Document::query()->sole();

    expect($document->path)->toBe('concepts/system-one');

    $this->get(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'concepts/system-one']))
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
    '先頭がスラッシュ' => '/leading-slash',
    '末尾がスラッシュ' => 'trailing-slash/',
    'スラッシュが連続' => 'double//slash',
    '階層内の大文字' => 'concepts/System-One',
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
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.visibility', DocumentVisibility::Public->value));
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
            'visibility' => DocumentVisibility::Public->value,
        ])
        ->assertRedirect(route('documents.show', $document));

    expect($document->fresh())
        ->title->toBe('変更後のタイトル')
        ->content->toBe('変更後の本文')
        ->visibility->toBe(DocumentVisibility::Public);

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

test('ゲストへのサイドバーナビゲーションには同じネームスペースの公開ドキュメントのみ並ぶ', function () {
    $owner = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $owner->id, 'is_public' => true]);
    $document = Document::factory()->for($owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction',
        'title' => 'はじめに',
        'visibility' => DocumentVisibility::Public,
    ]);
    $privateSibling = Document::factory()->for($owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'secret',
        'title' => '非公開ページ',
        'visibility' => DocumentVisibility::Private,
    ]);

    $this->get(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'introduction']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->has('namespaceDocuments', 1)
            ->where('namespaceDocuments.0.id', $document->id));
});

test('ネームスペースのオーナーへのサイドバーナビゲーションには自分の非公開ドキュメントも並ぶ', function () {
    $owner = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $owner->id]);
    $document = Document::factory()->for($owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction',
        'title' => 'はじめに',
        'visibility' => DocumentVisibility::Public,
    ]);
    $privateSibling = Document::factory()->for($owner)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'secret',
        'title' => '非公開ページ',
        'visibility' => DocumentVisibility::Private,
    ]);

    $this->actingAs($owner)
        ->get(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'introduction']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->has('namespaceDocuments', 2)
            ->where('namespaceDocuments.0.id', $document->id)
            ->where('namespaceDocuments.1.id', $privateSibling->id));
});

test('ネームスペースに属さないドキュメントのサイドバーナビゲーションは空になる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->has('namespaceDocuments', 0));
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

test('原文を含むドキュメント作成時に初期スナップショットが記録され自動的に採用される', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('documents.store', $namespace), [
            'title' => '翻訳記事',
            'content' => '翻訳された本文',
            'visibility' => DocumentVisibility::Public->value,
            'source_url' => 'https://example.com/source.md',
            'source_content' => 'Original text',
        ])
        ->assertRedirect(route('namespaces.show', $namespace));

    $document = Document::query()->sole();
    $snapshot = DocumentSourceSnapshot::query()->sole();

    expect($snapshot->document_id)->toBe($document->id)
        ->and($snapshot->content)->toBe('Original text')
        ->and($snapshot->content_hash)->toBe(hash('sha256', 'Original text'))
        ->and($document->document_source_snapshot_id)->toBe($snapshot->id);
});

test('原文URLがないドキュメントは鮮度を確認できない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create(['source_url' => null]);

    $this->actingAs($user)
        ->post(route('documents.refresh-source', $document))
        ->assertStatus(422);
});

test('原文に変更がない場合は新しいスナップショットは作られない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'source_url' => 'https://example.com/source.md',
        'source_content' => 'Same content',
    ]);
    $snapshot = DocumentSourceSnapshot::factory()->for($document)->create([
        'content' => 'Same content',
        'content_hash' => hash('sha256', 'Same content'),
    ]);
    $document->update(['document_source_snapshot_id' => $snapshot->id]);

    Http::fake(['*' => Http::response('Same content')]);

    $this->actingAs($user)
        ->post(route('documents.refresh-source', $document))
        ->assertRedirect(route('documents.edit', $document));

    expect(DocumentSourceSnapshot::query()->count())->toBe(1)
        ->and($document->fresh()->document_source_snapshot_id)->toBe($snapshot->id);
});

test('原文が変更されている場合は新しいスナップショットが記録されるがまだ採用されない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'source_url' => 'https://example.com/source.md',
        'source_content' => 'Old content',
    ]);
    $oldSnapshot = DocumentSourceSnapshot::factory()->for($document)->create([
        'content' => 'Old content',
        'content_hash' => hash('sha256', 'Old content'),
    ]);
    $document->update(['document_source_snapshot_id' => $oldSnapshot->id]);

    Http::fake(['*' => Http::response("# New Title\nNew content")]);

    $this->actingAs($user)
        ->post(route('documents.refresh-source', $document))
        ->assertRedirect(route('documents.edit', $document));

    expect(DocumentSourceSnapshot::query()->count())->toBe(2)
        ->and($document->fresh()->document_source_snapshot_id)->toBe($oldSnapshot->id)
        ->and($document->fresh()->source_content)->toBe('Old content');

    $newSnapshot = DocumentSourceSnapshot::query()->latest('id')->first();

    expect($newSnapshot->content)->toBe("# New Title {#new-title}\nNew content")
        ->and($newSnapshot->title)->toBe('New Title');

    $this->actingAs($user)
        ->get(route('documents.edit', $document))
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/edit')
            ->where('pendingSnapshot.id', $newSnapshot->id));
});

test('新しいスナップショットを取り込むと原文が更新される', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'source_url' => 'https://example.com/source.md',
        'source_title' => '古いタイトル',
        'source_content' => 'Old content',
    ]);
    $oldSnapshot = DocumentSourceSnapshot::factory()->for($document)->create([
        'content' => 'Old content',
        'content_hash' => hash('sha256', 'Old content'),
    ]);
    $document->update(['document_source_snapshot_id' => $oldSnapshot->id]);

    $newSnapshot = DocumentSourceSnapshot::factory()->for($document)->create([
        'content' => 'New content',
        'content_hash' => hash('sha256', 'New content'),
        'title' => '新しいタイトル',
    ]);

    $this->actingAs($user)
        ->post(route('documents.source-snapshots.adopt', [$document, $newSnapshot]))
        ->assertRedirect(route('documents.edit', $document));

    $document->refresh();

    expect($document->source_content)->toBe('New content')
        ->and($document->source_title)->toBe('新しいタイトル')
        ->and($document->document_source_snapshot_id)->toBe($newSnapshot->id);
});

test('他のユーザーのドキュメントは鮮度確認も取り込みもできない', function () {
    $owner = User::factory()->create();
    $document = Document::factory()->for($owner)->create([
        'source_url' => 'https://example.com/source.md',
        'source_content' => 'Content',
    ]);
    $snapshot = DocumentSourceSnapshot::factory()->for($document)->create();

    $other = User::factory()->create();

    Http::fake(['*' => Http::response('Content')]);

    $this->actingAs($other)
        ->post(route('documents.refresh-source', $document))
        ->assertForbidden();

    $this->actingAs($other)
        ->post(route('documents.source-snapshots.adopt', [$document, $snapshot]))
        ->assertForbidden();
});

test('別のドキュメントに属するスナップショットは取り込めない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();
    $otherDocument = Document::factory()->for($user)->create();
    $snapshot = DocumentSourceSnapshot::factory()->for($otherDocument)->create();

    $this->actingAs($user)
        ->post(route('documents.source-snapshots.adopt', [$document, $snapshot]))
        ->assertNotFound();
});

test('本文かタイトルを変更する更新は変更前の内容をリビジョンとして記録する', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'title' => '元のタイトル',
        'content' => '元の本文',
    ]);

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'title' => '新しいタイトル',
            'content' => '新しい本文',
            'visibility' => $document->visibility->value,
        ])
        ->assertRedirect(route('documents.show', $document));

    $revision = DocumentRevision::query()->sole();

    expect($revision->document_id)->toBe($document->id)
        ->and($revision->user_id)->toBe($user->id)
        ->and($revision->title)->toBe('元のタイトル')
        ->and($revision->content)->toBe('元の本文');
});

test('本文もタイトルも変わらない更新ではリビジョンは作られない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'title' => '同じタイトル',
        'content' => '同じ本文',
        'visibility' => DocumentVisibility::Private,
    ]);

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'title' => '同じタイトル',
            'content' => '同じ本文',
            'visibility' => DocumentVisibility::Public->value,
        ])
        ->assertRedirect(route('documents.show', $document));

    expect(DocumentRevision::query()->count())->toBe(0);
});

test('AI翻訳で本文を上書きすると翻訳前の本文がリビジョンとして記録される', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'content' => '元の本文',
        'source_content' => 'Original text',
    ]);

    TranslatorAgent::fake(['翻訳された本文']);

    $this->actingAs($user)
        ->post(route('documents.translate', $document))
        ->assertRedirect(route('documents.edit', $document));

    $revision = DocumentRevision::query()->sole();

    expect($revision->document_id)->toBe($document->id)
        ->and($revision->user_id)->toBe($user->id)
        ->and($revision->content)->toBe('元の本文')
        ->and($document->fresh()->content)->toBe('翻訳された本文');
});

test('リビジョンを復元すると本文とタイトルが置き換わり復元前の内容もリビジョンとして残る', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'title' => '現在のタイトル',
        'content' => '現在の本文',
    ]);
    $revision = DocumentRevision::factory()->for($document)->create([
        'title' => '過去のタイトル',
        'content' => '過去の本文',
    ]);

    $this->actingAs($user)
        ->post(route('documents.revisions.restore', [$document, $revision]))
        ->assertRedirect(route('documents.edit', $document));

    expect($document->fresh())
        ->title->toBe('過去のタイトル')
        ->content->toBe('過去の本文');

    expect(DocumentRevision::query()->count())->toBe(2);

    $backupRevision = DocumentRevision::query()->latest('id')->first();

    expect($backupRevision->id)->not->toBe($revision->id)
        ->and($backupRevision->title)->toBe('現在のタイトル')
        ->and($backupRevision->content)->toBe('現在の本文');
});

test('他のユーザーのドキュメントのリビジョンは復元できない', function () {
    $owner = User::factory()->create();
    $document = Document::factory()->for($owner)->create();
    $revision = DocumentRevision::factory()->for($document)->create();

    $other = User::factory()->create();

    $this->actingAs($other)
        ->post(route('documents.revisions.restore', [$document, $revision]))
        ->assertForbidden();
});

test('別のドキュメントに属するリビジョンは復元できない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();
    $otherDocument = Document::factory()->for($user)->create();
    $revision = DocumentRevision::factory()->for($otherDocument)->create();

    $this->actingAs($user)
        ->post(route('documents.revisions.restore', [$document, $revision]))
        ->assertNotFound();
});

test('名前空間に属するドキュメントのID URLはslug URLへリダイレクトされる', function () {
    $user = User::factory()->create();
    $namespace = DocumentNamespace::factory()->create(['slug' => 'typesafe', 'is_public' => true]);
    $document = Document::factory()->for($user)->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction/quickstart',
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->get(route('documents.show', $document))
        ->assertRedirect(route('documents.show-by-path', ['namespace' => $namespace, 'path' => 'introduction/quickstart']));
});

test('非公開ドキュメントのID URLはslugを漏らさず認可エラーになる', function () {
    $namespace = DocumentNamespace::factory()->create(['slug' => 'typesafe']);
    $document = Document::factory()->for(User::factory()->create())->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'secret',
        'visibility' => DocumentVisibility::Private,
    ]);

    $this->get(route('documents.show', $document))->assertForbidden();
});

test('ダッシュボードは名前空間のslugとpathを渡す', function () {
    $namespace = DocumentNamespace::factory()->create(['slug' => 'typesafe']);
    Document::factory()->for(User::factory()->create())->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction',
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('recentDocuments.0.path', 'introduction')
            ->where('recentDocuments.0.namespace.slug', 'typesafe'));
});
