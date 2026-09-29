<?php

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('2人のテストユーザーとTypeSafeドキュメントの翻訳サンプルを作成する', function () {
    $this->seed();

    $users = User::query()->orderBy('id')->get();

    expect($users)->toHaveCount(2)
        ->and(Document::query()->count())->toBe(8);

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

test('use-case-mapの翻訳済み本文がシードされ、JSX属性は原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'concepts/use-case-map')->sole();

    expect($document->title)->toBe('ユースケース例')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/concepts/use-case-map.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/concepts/use-case-map')
        ->and($document->source_content)->toContain('<AccordionGroup>')
        ->and($document->content)->toStartWith('# ユースケース例')
        ->and($document->content)->toContain('<Columns cols={2}>')
        ->and($document->content)->toContain('<Card title="AI自動化ソフトウェア" icon="blocks">')
        ->and(substr_count($document->content, '<Accordion '))->toBe(19)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('system-oneの翻訳済み本文がシードされる', function () {
    $this->seed();

    $document = Document::query()->where('path', 'concepts/system-one')->sole();

    expect($document->source_url)->toBe('https://docs.typesafe.ai/concepts/system-one.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/concepts/system-one')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# System One')
        ->and($document->content)->toContain('フラッグシップモデル')
        ->and(substr_count($document->content, '<Note>'))->toBe(2)
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('stateの翻訳済み本文がシードされ、コードブロックは原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'concepts/state')->sole();

    expect($document->title)->toBe('状態')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/concepts/state.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/concepts/state')
        ->and($document->source_content)->toStartWith('> ## Documentation Index')
        ->and($document->content)->toStartWith('# 状態')
        ->and($document->content)->toContain('"refund_policy": "Duplicate charges are eligible for a refund."')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('primitivesの翻訳済み本文がシードされ、平坦化したコードブロックとJSX属性は原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'primitives')->sole();

    expect($document->title)->toBe('プリミティブ（質問）')
        ->and($document->source_url)->toBe('https://docs.typesafe.ai/primitives.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/primitives')
        ->and($document->source_content)->not->toContain('export function')
        ->and($document->source_content)->not->toContain('TypesafeExample')
        ->and($document->content)->toStartWith('# プリミティブ（質問）')
        ->and($document->content)->toContain('```json title="request" theme={null}')
        ->and($document->content)->toContain('<Columns cols={3}>')
        ->and($document->content)->toContain('<Card title="Score" href="/primitives/score" icon="gauge">')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('primitives/choiceの翻訳済み本文がシードされ、コードブロックは原文のまま残る', function () {
    $this->seed();

    $document = Document::query()->where('path', 'primitives/choice')->sole();

    expect($document->source_url)->toBe('https://docs.typesafe.ai/primitives/choice.md')
        ->and($document->canonical_url)->toBe('https://docs.typesafe.ai/primitives/choice')
        ->and($document->source_content)->not->toContain('TypesafeExample')
        ->and($document->content)->toStartWith('# Choice')
        ->and($document->content)->toContain('確信度')
        ->and(substr_count($document->content, '```json title="request" theme={null}'))->toBe(3)
        ->and($document->content)->toContain('"choice": "returns"')
        ->and($document->adoptedSourceSnapshot)->not->toBeNull();
});

test('typesafeのナビゲーションは原本どおりPrimitives系のページをStateとConfidenceの間に並べる', function () {
    $this->seed();

    $concepts = collect(DocumentNamespace::query()->where('slug', 'typesafe')->sole()->navigation)
        ->firstWhere('title', 'コンセプト');

    expect($concepts['pages'])->toBe([
        'concepts/system-one',
        'concepts/state',
        'primitives',
        'primitives/choice',
        'primitives/score',
        'primitives/noul',
        'primitives/advanced',
        'confidence',
        'concepts/how-to-build-with-system-one',
        'introduction/machine-learning-primer',
    ]);
});
