<?php

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('未認証ユーザーはいいねからログイン画面へリダイレクトされる', function () {
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->post(route('documents.likes.store', $document))->assertRedirect(route('login'));
    $this->delete(route('documents.likes.destroy', $document))->assertRedirect(route('login'));
});

test('他のユーザーの公開ドキュメントにいいねできる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($user)
        ->post(route('documents.likes.store', $document))
        ->assertRedirect();

    expect($document->likes()->where('user_id', $user->id)->exists())->toBeTrue();

    $this->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('likesCount', 1)
            ->where('liked', true));
});

test('いいねは同じユーザーが重ねて押しても1件のまま', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($user);
    $this->post(route('documents.likes.store', $document));
    $this->post(route('documents.likes.store', $document));

    expect($document->likes()->count())->toBe(1);
});

test('いいねを取り消せる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($user);
    $this->post(route('documents.likes.store', $document));

    $this->delete(route('documents.likes.destroy', $document))
        ->assertRedirect();

    expect($document->likes()->where('user_id', $user->id)->exists())->toBeFalse();
});

test('自分のドキュメントにもいいねできる', function () {
    $user = User::factory()->create();
    $document = Document::factory()->for($user)->create([
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($user)
        ->post(route('documents.likes.store', $document))
        ->assertRedirect();

    expect($document->likes()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('閲覧できない非公開ドキュメントにはいいねできない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Private,
    ]);

    $this->actingAs($user)
        ->post(route('documents.likes.store', $document))
        ->assertForbidden();

    expect($document->likes()->count())->toBe(0);
});

test('いいね数は他人からは誰がいいねしたか分からずカウントのみ取得できる', function () {
    $viewer = User::factory()->create();
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Public,
    ]);

    $likers = User::factory()->count(3)->create();
    foreach ($likers as $liker) {
        $document->likes()->create(['user_id' => $liker->id]);
    }

    $this->actingAs($viewer)
        ->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('documents/show')
            ->where('likesCount', 3)
            ->where('liked', false)
            ->missing('document.likes')
            ->missing('likers'));
});

test('いいねの部分リロードではドキュメント本文が再送されない', function () {
    $user = User::factory()->create();
    $document = Document::factory()->create([
        'visibility' => DocumentVisibility::Public,
    ]);

    $this->actingAs($user);

    // Simulate the browser's real flow: an initial full page load, followed
    // by the AJAX partial reload the like button triggers, matching its
    // asset version.
    $initial = $this->get(route('documents.show', $document));
    $version = $initial->viewData('page')['version'];

    $response = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Component' => 'documents/show',
        'X-Inertia-Partial-Data' => 'likesCount,liked',
    ])->get(route('documents.show', $document));

    $response->assertSuccessful();

    $props = $response->json('props');

    expect($props)->toHaveKeys(['likesCount', 'liked'])
        ->and($props)->not->toHaveKey('document')
        ->and($props['likesCount'])->toBe(0)
        ->and($props['liked'])->toBeFalse();
});
