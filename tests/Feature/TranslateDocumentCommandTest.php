<?php

use App\Ai\Agents\TranslatorAgent;
use App\Models\Document;
use App\Models\DocumentNamespace;
use App\Models\User;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;

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

test('the command compresses oversized link destinations and restores them in translated content', function () {
    $htmlUrl = 'https://example.test/playground#share/'.str_repeat('abC123_-', 80);
    $markdownUrl = 'https://example.test/reference/'.str_repeat('xyZ789_-', 50);
    $source = "# Example\n\n<a href=\"{$htmlUrl}\" target=\"_blank\">Open the example</a>\n\n[Read more]({$markdownUrl})";
    $document = documentWithSource($source);

    TranslatorAgent::fake([
        "# 例\n\n[例を開く](__PRESERVED_URL_0__)\n\n[詳しく読む](__PRESERVED_URL_1__)",
    ]);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertSuccessful();

    TranslatorAgent::assertPrompted(function (AgentPrompt $prompt) use ($htmlUrl, $markdownUrl): bool {
        return $prompt->contains('[Open the example](__PRESERVED_URL_0__)')
            && $prompt->contains('[Read more](__PRESERVED_URL_1__)')
            && ! $prompt->contains($htmlUrl)
            && ! $prompt->contains($markdownUrl);
    });

    expect($document->fresh()->content)->toBe(
        "# 例\n\n[例を開く]({$htmlUrl})\n\n[詳しく読む]({$markdownUrl})",
    );
});

test('the command leaves ordinary links and fenced code unchanged', function () {
    $shortUrl = 'https://example.test/guide';
    $longCodeUrl = 'https://example.test/'.str_repeat('code-', 70);
    $codeBlock = "```php\n\$url = '{$longCodeUrl}';\n```";
    $source = "# Quick start\n\n[Guide]({$shortUrl})\n\n{$codeBlock}";
    documentWithSource($source);

    TranslatorAgent::fake(["# クイックスタート\n\n[ガイド]({$shortUrl})\n\n{$codeBlock}"]);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertSuccessful();

    TranslatorAgent::assertPrompted("# Quick start\n\n[Guide]({$shortUrl})\n\n{$codeBlock}");
});

test('the command keeps the existing document when a preserved link marker is omitted', function () {
    $url = 'https://example.test/'.str_repeat('oversized-', 40);
    $document = documentWithSource("# Quick start\n\n[Guide]({$url})");
    $originalContent = $document->content;

    TranslatorAgent::fake(['# クイックスタート']);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertFailed();

    TranslatorAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('[Guide](__PRESERVED_URL_0__)'));
    expect($document->fresh()->content)->toBe($originalContent)
        ->and($document->revisions()->count())->toBe(0);
});

test('the command reports token usage and estimated cost for the configured model', function () {
    documentWithSource('Hello');
    config([
        'ai.providers.bedrock.pricing' => [
            'model' => 'jp.anthropic.claude-sonnet-4-6',
            'input_per_million_tokens' => 3.00,
            'output_per_million_tokens' => 15.00,
        ],
    ]);

    TranslatorAgent::fake([
        new AgentResponse(
            'fake-invocation',
            'こんにちは',
            new TextUsage(inputTokens: 1_000, outputTokens: 2_000),
            new Meta(provider: 'bedrock', model: 'jp.anthropic.claude-sonnet-4-6'),
        ),
    ]);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->expectsOutputToContain('jp.anthropic.claude-sonnet-4-6')
        ->expectsOutputToContain('1,000')
        ->expectsOutputToContain('2,000')
        ->expectsOutputToContain('$0.033000')
        ->assertSuccessful();
});

test('the command does not estimate cost when the response model has no matching price', function () {
    documentWithSource('Hello');

    TranslatorAgent::fake([
        new AgentResponse(
            'fake-invocation',
            'こんにちは',
            new TextUsage(inputTokens: 1_000, outputTokens: 2_000),
            new Meta(provider: 'bedrock', model: 'different-model'),
        ),
    ]);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->expectsOutputToContain('different-model')
        ->expectsOutputToContain('unavailable')
        ->assertSuccessful();
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

test('a long source is translated in chunks whose usage is summed and whose parts are rejoined', function () {
    $section = fn (string $heading) => "## {$heading}\n\n".implode("\n", array_fill(0, 200, 'A sentence of ordinary prose that costs tokens.'))."\n\n";
    $document = documentWithSource("# Title\n\n".$section('One').$section('Two').$section('Three').'End');

    $response = fn (string $text) => new AgentResponse(
        'fake-invocation',
        $text,
        new TextUsage(inputTokens: 1_000, outputTokens: 2_000),
        new Meta(provider: 'bedrock', model: 'jp.anthropic.claude-sonnet-4-6'),
    );

    TranslatorAgent::fake([$response("# 題\n\n## 一\n"), $response("## 二\n"), $response("## 三\n\n終")]);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->expectsOutputToContain('Chunks')
        ->expectsOutputToContain('3,000')
        ->expectsOutputToContain('6,000')
        ->assertSuccessful();

    expect($document->fresh()->content)->toBe("# 題\n\n## 一\n\n## 二\n\n## 三\n\n終");
});

test('nothing is written when a later chunk fails', function () {
    $section = fn (string $heading) => "## {$heading}\n\n".implode("\n", array_fill(0, 200, 'A sentence of ordinary prose that costs tokens.'))."\n\n";
    $document = documentWithSource("# Title\n\n".$section('One').$section('Two').'End');
    $originalContent = $document->content;

    TranslatorAgent::fake([
        '# 題',
        fn () => throw new RuntimeException('Bedrock timed out'),
    ]);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertFailed();

    expect($document->fresh()->content)->toBe($originalContent)
        ->and($document->revisions()->count())->toBe(0);
});

test('the command compresses oversized reference-style link definitions and restores them', function () {
    $url = 'https://example.test/playground#share/'.str_repeat('abC123_-', 80);
    $document = documentWithSource("# Example\n\n[Open][playground]\n\n[playground]: {$url}");

    TranslatorAgent::fake(["# 例\n\n[開く][playground]\n\n[playground]: __PRESERVED_URL_0__"]);

    $this->artisan('documents:translate', ['address' => 'typesafe/introduction/quickstart'])
        ->assertSuccessful();

    TranslatorAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('[playground]: __PRESERVED_URL_0__')
        && ! $prompt->contains($url));
    expect($document->fresh()->content)->toBe("# 例\n\n[開く][playground]\n\n[playground]: {$url}");
});
