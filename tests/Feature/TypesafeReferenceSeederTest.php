<?php

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\DocumentNamespace;
use Database\Seeders\TypesafeReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ReferenceとSDKの71ページを原文と採用済みスナップショット付きで登録する', function () {
    $namespace = DocumentNamespace::factory()->create([
        'slug' => 'typesafe',
        'source_url' => 'https://docs.typesafe.ai',
    ]);

    $this->seed(TypesafeReferenceSeeder::class);

    $documents = $namespace->documents()->with('adoptedSourceSnapshot')->get();

    expect($documents)->toHaveCount(71);

    foreach ($documents as $document) {
        expect($document->content)->not->toBeEmpty()
            ->and($document->content)->toBe($document->source_content)
            ->and($document->title)->toBe($document->source_title)
            ->and($document->visibility)->toBe(DocumentVisibility::Public)
            ->and($document->document_type)->toBe(DocumentType::Translation)
            ->and($document->user_id)->toBe($namespace->owner_user_id)
            ->and($document->adoptedSourceSnapshot?->content)->toBe($document->source_content);
    }

    $models = $documents->firstWhere('path', 'models');

    expect($models->title)->toBe('Models')
        ->and($models->source_url)->toBe('https://docs.typesafe.ai/models.md')
        ->and($models->canonical_url)->toBe('https://docs.typesafe.ai/models');

    $namespace->refresh();
    $tree = $namespace->navigationTree($documents);

    expect(array_column($tree, 'title'))->toBe(['Reference', 'Client SDKs'])
        ->and($tree[0]['children'][4]['title'])->toBe('Model jaggedness')
        ->and($tree[0]['children'][4]['children'][0]['document']->path)->toBe('model-jaggedness/jev-1.13')
        ->and($tree[1]['children'][1]['title'])->toBe('Python SDK')
        ->and($tree[1]['children'][1]['children'][3]['children'][1]['title'])->toBe('Clients')
        ->and($tree[1]['children'][1]['children'][3]['children'][1]['children'])->toHaveCount(2)
        ->and($tree[1]['children'][2]['title'])->toBe('JavaScript SDK')
        ->and(array_column($tree[1]['children'][2]['children'][2]['children'], 'title'))->toBe([
            'API reference', 'Classes', 'Interfaces', 'Type Aliases', 'Variables', 'Functions',
        ])
        ->and($tree[1]['children'][2]['children'][2]['children'][1]['children'])->toHaveCount(14)
        ->and($tree[1]['children'][2]['children'][2]['children'][2]['children'])->toHaveCount(18);

    $orderedPaths = $namespace->navigationDocuments($documents)->pluck('path');

    expect($orderedPaths)->toHaveCount(71)
        ->and($orderedPaths->unique())->toHaveCount(71)
        ->and($orderedPaths->first())->toBe('models')
        ->and($orderedPaths->last())->toBe('sdk/javascript/api/functions/score');
});

test('再投入しても既存の訳文と原文と他グループを維持し文書とスナップショットは重複しない', function () {
    $namespace = DocumentNamespace::factory()->create([
        'slug' => 'typesafe',
        'source_url' => 'https://docs.typesafe.ai',
        'navigation' => [['title' => '既存グループ', 'pages' => ['introduction']]],
    ]);
    $existing = Document::factory()->create([
        'document_namespace_id' => $namespace->id,
        'user_id' => $namespace->owner_user_id,
        'path' => 'models',
        'title' => 'モデル',
        'content' => '# モデル\n既存の翻訳',
        'source_title' => 'Original models',
        'source_content' => '# Original models',
    ]);
    $snapshot = $existing->sourceSnapshots()->create([
        'content' => $existing->source_content,
        'content_hash' => hash('sha256', $existing->source_content),
        'title' => $existing->source_title,
        'fetched_at' => now(),
    ]);
    $existing->update(['document_source_snapshot_id' => $snapshot->id]);

    $this->seed(TypesafeReferenceSeeder::class);
    $this->seed(TypesafeReferenceSeeder::class);

    $existing->refresh();
    $namespace->refresh();

    expect($namespace->documents()->count())->toBe(71)
        ->and($existing->title)->toBe('モデル')
        ->and($existing->content)->toBe('# モデル\n既存の翻訳')
        ->and($existing->source_title)->toBe('Original models')
        ->and($existing->source_content)->toBe('# Original models')
        ->and($existing->document_source_snapshot_id)->toBe($snapshot->id)
        ->and($namespace->navigation)->toHaveCount(3)
        ->and($namespace->navigation[0])->toBe(['title' => '既存グループ', 'pages' => ['introduction']]);

    expect($existing->revisions()->count())->toBe(0);

    foreach ($namespace->documents()->get() as $document) {
        expect($document->sourceSnapshots()->count())->toBe(1);
    }
});
