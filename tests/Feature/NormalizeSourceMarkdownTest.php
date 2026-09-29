<?php

use App\Actions\NormalizeSourceMarkdown;

function pageWithTypesafeExample(string $call): string
{
    return <<<MD
        # Title

        export function TypesafeExample({example, display, title}) {
          const helper = "x";
          return <div>{helper}</div>;
        }

        Intro text.

        {$call}

        After.
        MD;
}

test('the inline component definition is dropped and the call becomes a JSON code block', function () {
    $markdown = pageWithTypesafeExample(<<<'JSX'
        <TypesafeExample
          example={{
        state: 'Refund me, don\'t wait',
        questions: {
          // whether a refund is wanted
          refund: {
            type: 'noul',
            instructions: `Does the customer want a refund?`,
          },
        },
        }}
        />
        JSX);

    $result = app(NormalizeSourceMarkdown::class)->flattenTypesafeExamples($markdown);

    expect($result)
        ->not->toContain('export function')
        ->not->toContain('TypesafeExample')
        ->toContain('```json title="request" theme={null}')
        ->toContain('"state": "Refund me, don\'t wait"')
        ->toContain('"instructions": "Does the customer want a refund?"')
        ->toContain("Intro text.\n\n```json")
        ->toContain("```\n\nAfter.");
});

test('display=questions shows only the questions and title names the code block', function () {
    $markdown = pageWithTypesafeExample(<<<'JSX'
        <TypesafeExample
          title="Decomposed questions (good)"
          display="questions"
          example={{ state: 'ignored', questions: { a: { type: 'noul', instructions: 'A?' } } }}
        />
        JSX);

    $result = app(NormalizeSourceMarkdown::class)->flattenTypesafeExamples($markdown);

    expect($result)
        ->toContain('```json title="Decomposed questions (good)" theme={null}')
        ->toContain('"instructions": "A?"')
        ->not->toContain('ignored');
});

test('several calls in one page are all flattened', function () {
    $call = <<<'JSX'
        <TypesafeExample example={{ questions: { a: { type: 'noul', instructions: 'A?' } } }} />
        JSX;
    $markdown = pageWithTypesafeExample($call."\n\n".$call);

    $result = app(NormalizeSourceMarkdown::class)->flattenTypesafeExamples($markdown);

    expect(substr_count($result, '```json title="request"'))->toBe(2)
        ->and($result)->not->toContain('TypesafeExample');
});

test('headings outside code fences get anchors and existing anchors are kept', function () {
    $markdown = <<<'MD'
        > ## Documentation Index
        # Primitives (Questions)

        ## Already anchored {#custom}

        ```bash
        # not a heading
        ```

        ### Ask speculative questions
        MD;

    $result = app(NormalizeSourceMarkdown::class)->addHeadingAnchors($markdown);

    expect($result)
        ->toContain('> ## Documentation Index {#documentation-index}')
        ->toContain('# Primitives (Questions) {#primitives-questions}')
        ->toContain('## Already anchored {#custom}')
        ->toContain("```bash\n# not a heading\n```")
        ->toContain('### Ask speculative questions {#ask-speculative-questions}');
});

test('interactive explorer widgets lose their definition and their call', function () {
    $markdown = <<<'MD'
        # Score

        export function ScoreExplorer() {
          const [selected, setSelected] = useState(0);
          return <section>{selected}</section>;
        }

        Before.

        <ScoreExplorer />

        After.

        <ConfidenceExplorer />
        MD;

    $result = app(NormalizeSourceMarkdown::class)->removeInteractiveWidgets($markdown);

    expect($result)
        ->not->toContain('Explorer')
        ->not->toContain('export function')
        ->toContain("Before.\n\nAfter.");
});

test('a raw HTML table with JSX attributes becomes a Markdown table with the spanning header folded in', function () {
    $markdown = <<<'MD'
        Intro.

        <table>
          <thead>
            <tr>
              <th colSpan={2} />
              <th colSpan={2} style={{ textAlign: 'left' }}><code>probabilities</code></th>
            </tr>

            <tr>
              <th style={{ width: '44%' }}>State</th>
              <th><code>score</code></th>
              <th>Level 0</th>
              <th>Level 1</th>
            </tr>
          </thead>

          <tbody>
            <tr>
              <td>A | B</td>
              <td>0.0</td><td>1.0</td><td>0.0</td>
            </tr>
          </tbody>
        </table>

        Outro.
        MD;

    $result = app(NormalizeSourceMarkdown::class)->flattenHtmlTables($markdown);

    expect($result)
        ->not->toContain('<table')
        ->toContain("| State | `score` | `probabilities`: Level 0 | `probabilities`: Level 1 |\n| - | - | - | - |\n| A \\| B | 0.0 | 1.0 | 0.0 |")
        ->toContain('Intro.')
        ->toContain('Outro.');
});

test('markdown without an HTML table is left untouched by the table flattening', function () {
    $markdown = "| a | b |\n| - | - |\n| 1 | 2 |\n";

    expect(app(NormalizeSourceMarkdown::class)->flattenHtmlTables($markdown))->toBe($markdown);
});
