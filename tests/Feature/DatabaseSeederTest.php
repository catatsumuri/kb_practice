<?php

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('2人のテストユーザーとTypeSafeドキュメントの翻訳サンプルを作成する', function () {
    $this->seed();

    $users = User::query()->orderBy('id')->get();

    expect($users)->toHaveCount(2)
        ->and(Document::query()->count())->toBe(4);

    $document = Document::query()->where('path', 'introduction')->sole();

    expect($document->user_id)->toBe($users->first()->id)
        ->and($document->visibility)->toBe(DocumentVisibility::Public)
        ->and($document->document_type)->toBe(DocumentType::Translation)
        ->and($document->source_title)->toBe('Introduction')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/introduction.md')
        ->and($document->source_author)->toBe('TypeSafe')
        ->and($document->source_content)->not->toBeEmpty();
});

test('quickstartの翻訳済み本文がシードされる', function () {
    $this->seed();

    $document = Document::query()->where('path', 'introduction/quickstart')->sole();

    expect($document->title)->toBe('クイックスタート')
        ->and($document->content)->toStartWith('# クイックスタート')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('coding-agentsの翻訳済み本文がシードされる', function () {
    $this->seed();

    $document = Document::query()->where('path', 'introduction/coding-agents')->sole();

    expect($document->title)->toBe('コーディングエージェントとJev')
        ->and($document->content)->toStartWith('# コーディングエージェントとJev')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/introduction/coding-agents.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/introduction/coding-agents')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('use-case-mapは原本を取り込んだだけで未翻訳のままシードされる', function () {
    $this->seed();

    $document = Document::query()->where('path', 'concepts/use-case-map')->sole();

    expect($document->source_url)->toBe('https://docs.typesafe.ai/concepts/use-case-map.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/concepts/use-case-map')
        ->and($document->source_content)->toContain('<AccordionGroup>')
        ->and($document->content)->toBe($document->source_content)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});
