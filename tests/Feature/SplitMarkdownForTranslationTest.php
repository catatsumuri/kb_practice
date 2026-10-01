<?php

use App\Actions\SplitMarkdownForTranslation;

function splitMarkdown(string $markdown, int $maxTokens): array
{
    return (new SplitMarkdownForTranslation)($markdown, $maxTokens);
}

function sectionOfProse(string $heading, int $lines): string
{
    return "## {$heading}\n\n".implode("\n", array_fill(0, $lines, 'A sentence of ordinary prose that costs tokens.'))."\n\n";
}

test('a short document is a single chunk', function () {
    $chunks = splitMarkdown("# Title\n\nHello", 8000);

    expect($chunks)->toBe([['text' => "# Title\n\nHello", 'separator' => '']]);
});

test('a long document is cut before headings and rejoins to the original', function () {
    $markdown = "# Title\n\n".sectionOfProse('One', 12).sectionOfProse('Two', 12).sectionOfProse('Three', 12).'End';

    $chunks = splitMarkdown($markdown, 400);

    expect($chunks)->toHaveCount(3)
        ->and($chunks[1]['text'])->toStartWith('## Two')
        ->and($chunks[2]['text'])->toStartWith('## Three')
        ->and((new SplitMarkdownForTranslation)->join($chunks, array_column($chunks, 'text')))->toBe($markdown);
});

test('a long code block is cut at a blank line inside it and rejoined exactly', function () {
    $block = implode("\n\n", array_fill(0, 30, 'print("a line of code that is long enough to count")'));
    $markdown = "# Title\n\n```python\n{$block}\n```\n\nAfter";

    $chunks = splitMarkdown($markdown, 300);

    expect(count($chunks))->toBeGreaterThan(1)
        ->and((new SplitMarkdownForTranslation)->join($chunks, array_column($chunks, 'text')))->toBe($markdown);

    $fences = array_sum(array_map(fn (array $chunk): int => preg_match_all('/^```/m', $chunk['text']), $chunks));

    expect($fences)->toBe(2);
});

test('a code fence line inside a longer fence does not end it', function () {
    $markdown = "````md\n```\nnested\n```\n````\n\n".sectionOfProse('Next', 10);

    $chunks = splitMarkdown($markdown, 10_000);

    expect($chunks)->toHaveCount(1);
});

test('every chunk stays within the budget when cut points exist', function () {
    $markdown = '';

    foreach (range(1, 12) as $number) {
        $markdown .= sectionOfProse("Section {$number}", 8);
    }

    $chunks = splitMarkdown(rtrim($markdown), 500);

    foreach ($chunks as $chunk) {
        expect(mb_strlen($chunk['text']) * 0.54)->toBeLessThan(500 * 1.2);
    }
});
