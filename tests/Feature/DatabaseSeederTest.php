<?php

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('2人のテストユーザーとTypeSafeドキュメントの翻訳サンプルを1件作成する', function () {
    $this->seed();

    $users = User::query()->orderBy('id')->get();

    expect($users)->toHaveCount(2)
        ->and(Document::query()->count())->toBe(1);

    $document = Document::query()->sole();

    expect($document->user_id)->toBe($users->first()->id)
        ->and($document->visibility)->toBe(DocumentVisibility::Public)
        ->and($document->document_type)->toBe(DocumentType::Translation)
        ->and($document->source_title)->toBe('Introduction')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/introduction.md')
        ->and($document->source_author)->toBe('TypeSafe')
        ->and($document->source_content)->not->toBeEmpty();
});
