<?php

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('未認証ユーザーはドキュメントからログイン画面へリダイレクトされる', function () {
    $document = Document::factory()->create();

    $this->get(route('documents.index'))->assertRedirect(route('login'));
    $this->get(route('documents.show', $document))->assertRedirect(route('login'));
});
