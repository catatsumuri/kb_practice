<?php

use App\Models\Document;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('未認証ユーザーはドキュメントにアクセスできない', function () {
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

test('作成したドキュメントはログインユーザーに紐づく', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [
            'title' => '自分のドキュメント',
            'content' => '本文',
        ])
        ->assertRedirect(route('documents.index'));

    $document = Document::query()->sole();

    expect($document->user->is($user))->toBeTrue();
});

test('自分のドキュメントは表示できる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('document.id', $document->id));
});

test('自分のドキュメントは変更や削除ができる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('documents.update', $document), [
            'title' => '変更後のタイトル',
            'content' => '変更後の本文',
        ])
        ->assertRedirect(route('documents.show', $document));

    expect($document->fresh())
        ->title->toBe('変更後のタイトル')
        ->content->toBe('変更後の本文');

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
    ])->assertForbidden();
    $this->delete(route('documents.destroy', $document))->assertForbidden();

    expect($document->fresh())
        ->title->toBe('他のユーザーのドキュメント')
        ->content->toBe('変更前の本文');
});
