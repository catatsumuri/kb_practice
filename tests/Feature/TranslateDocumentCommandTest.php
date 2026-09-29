<?php

use App\Ai\Agents\TranslatorAgent;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;

function documentWithSource(string $sourceContent): Document
{
    $namespace = DocumentNamespace::factory()->create(['slug' => 'typesafe']);

    return Document::factory()->for(User::factory()->create())->create([
        'document_namespace_id' => $namespace->id,
        'path' => 'introduction/quickstart',
        'content' => '元の本文',
        'source_content' => $sourceContent,
    ]);
}

test('the command translates a document addressed by namespace and multi-segment path', function () {
    $document = documentWithSource("# Quick start\n\nHello");

    TranslatorAgent::fake(['# クイックスタート']);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertSuccessful();

    TranslatorAgent::assertPrompted("# Quick start\n\nHello");
    expect($document->fresh()->content)->toBe('# クイックスタート')
        ->and($document->revisions()->where('content', '元の本文')->exists())->toBeTrue();
});

test('the leading blockquote above the first heading is not sent to the translator', function () {
    documentWithSource("> ## Documentation Index\n> Fetch the index.\n\n# Quick start\n\nHello");

    TranslatorAgent::fake(['# クイックスタート']);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertSuccessful();

    TranslatorAgent::assertPrompted("# Quick start\n\nHello");
});

test('text before the first heading is kept when it is not a blockquote', function () {
    $document = documentWithSource("Intro paragraph\n\n# Title");

    expect($document->translationSource())->toBe("Intro paragraph\n\n# Title");
});

test('the command fails for an unknown document', function () {
    TranslatorAgent::fake();

    $this->artisan('documents:translate', ['address' => 'typesafe/missing'])
        ->assertFailed();

    TranslatorAgent::assertNeverPrompted();
});

test('the command fails for a document without source content', function () {
    $document = documentWithSource('placeholder');
    $document->update(['source_content' => null]);

    TranslatorAgent::fake();

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertFailed();

    TranslatorAgent::assertNeverPrompted();
    expect($document->fresh()->content)->toBe('元の本文');
});

test('the title is replaced by the translated leading heading, without its anchor', function () {
    $document = documentWithSource("# Quick start {#quick-start}\n\nHello");

    TranslatorAgent::fake(["# クイックスタート {#quick-start}\n\nこんにちは"]);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertSuccessful();

    expect($document->fresh()->title)->toBe('クイックスタート');
});

test('the title is kept when the translation has no leading heading', function () {
    $document = documentWithSource('Hello');
    $title = $document->title;

    TranslatorAgent::fake(['こんにちは']);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertSuccessful();

    expect($document->fresh()->title)->toBe($title);
});
