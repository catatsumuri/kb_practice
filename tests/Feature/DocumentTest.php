<?php

use App\Models\Document;

test('未認証ユーザーはドキュメントにアクセスできない', function () {
    $document = Document::factory()->create();

    $this->get(route('documents.index'))->assertRedirect(route('login'));
    $this->get(route('documents.show', $document))->assertRedirect(route('login'));
});
