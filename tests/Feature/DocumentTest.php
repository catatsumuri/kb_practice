<?php

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => '自分のドキュメント',
            'content' => '本文',
            'visibility' => DocumentVisibility::Public->value,
        ])
        ->assertRedirect(route('documents.index'));

    $document = Document::query()->sole();

    expect($document->user->is($user))->toBeTrue()
        ->and($document->visibility)->toBe(DocumentVisibility::Public);
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
