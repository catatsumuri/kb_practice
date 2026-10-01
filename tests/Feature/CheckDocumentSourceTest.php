<?php

use App\Actions\CheckDocumentSource;
use App\Enums\SourceCheckStatus;
use App\Models\Document;
use App\Models\DocumentNamespace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function documentWithAdoptedSource(string $content, array $attributes = []): Document
{
    $document = Document::factory()->create([
        'source_url' => 'https://example.com/source.md',
        'source_content' => $content,
        ...$attributes,
    ]);
    $document->recordSourceSnapshot($content, null, adopt: true);

    return $document;
}

test('原文が同じなら変更なし、違えば未採用のスナップショットを記録する', function () {
    $document = documentWithAdoptedSource('Same');

    Http::fake(['*' => Http::sequence()->push('Same')->push("# Title\nChanged")]);

    expect(app(CheckDocumentSource::class)($document)->status)->toBe(SourceCheckStatus::Unchanged)
        ->and($document->sourceSnapshots()->count())->toBe(1);

    $adoptedId = $document->document_source_snapshot_id;

    expect(app(CheckDocumentSource::class)($document->fresh())->status)->toBe(SourceCheckStatus::Updated)
        ->and($document->sourceSnapshots()->count())->toBe(2)
        ->and($document->fresh()->document_source_snapshot_id)->toBe($adoptedId);
});

test('取り込み待ちの変更を再チェックしても重複したスナップショットは作られない', function () {
    $document = documentWithAdoptedSource('Old');

    Http::fake(['*' => Http::response('New')]);

    expect(app(CheckDocumentSource::class)($document)->status)->toBe(SourceCheckStatus::Updated)
        ->and(app(CheckDocumentSource::class)($document->fresh())->status)->toBe(SourceCheckStatus::Pending)
        ->and($document->sourceSnapshots()->count())->toBe(2);
});

test('保存済みの原文は正規化済みなので、取得した原文も正規化して比較する', function () {
    $document = documentWithAdoptedSource("# Title {#title}\n\nBody\n");

    Http::fake(['*' => Http::response("# Title\n\nBody\n")]);

    expect(app(CheckDocumentSource::class)($document)->status)->toBe(SourceCheckStatus::Unchanged)
        ->and($document->sourceSnapshots()->count())->toBe(1);
});

test('取得に失敗した場合は失敗を返し、source_urlがなければスキップする', function () {
    Http::fake(['*' => Http::response('', 500)]);

    $failed = app(CheckDocumentSource::class)(documentWithAdoptedSource('Same'));
    $skipped = app(CheckDocumentSource::class)(Document::factory()->create(['source_url' => null]));

    expect($failed->status)->toBe(SourceCheckStatus::Failed)
        ->and($failed->message)->not->toBeNull()
        ->and($skipped->status)->toBe(SourceCheckStatus::Skipped);
});

test('コマンドは名前空間と記事で対象を絞り込める', function () {
    $namespace = DocumentNamespace::factory()->create(['slug' => 'ns-a']);
    $inNamespace = documentWithAdoptedSource('Old', ['document_namespace_id' => $namespace->id, 'path' => 'a']);
    $other = documentWithAdoptedSource('Old');

    Http::fake(['*' => Http::response('New')]);

    $this->artisan('documents:check-sources', ['--namespace' => 'ns-a'])->assertSuccessful();

    expect($inNamespace->sourceSnapshots()->count())->toBe(2)
        ->and($other->sourceSnapshots()->count())->toBe(1);

    $this->artisan('documents:check-sources', ['--document' => $other->id])->assertSuccessful();

    expect($other->sourceSnapshots()->count())->toBe(2);
});

test('コマンドは取得失敗があると失敗終了し、存在しない名前空間も失敗する', function () {
    $document = documentWithAdoptedSource('Old');
    Http::fake(['*' => Http::response('', 500)]);

    $this->artisan('documents:check-sources', ['--document' => $document->id])->assertFailed();
    $this->artisan('documents:check-sources', ['--namespace' => 'missing'])->assertFailed();
});
