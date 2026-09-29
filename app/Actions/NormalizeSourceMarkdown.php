<?php

namespace App\Actions;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

class NormalizeSourceMarkdown
{
    /**
     * Turn a page fetched from a Mintlify site into the Markdown this app
     * stores as a document's source: the in-page TypesafeExample component is
     * flattened into a plain code block, and headings get explicit anchors.
     */
    public function __invoke(string $markdown): string
    {
        return $this->addHeadingAnchors($this->flattenTypesafeExamples($markdown));
    }

    /**
     * Drop the inline `export function TypesafeExample` definition and
     * replace each `<TypesafeExample ... />` call with the fenced JSON block
     * it would have displayed (the "Try it in the Playground" link is not
     * carried over).
     */
    public function flattenTypesafeExamples(string $markdown): string
    {
        $markdown = preg_replace('/^export function TypesafeExample\(.*?^\}\n\n?/ms', '', $markdown);

        while (($start = strpos($markdown, '<TypesafeExample')) !== false) {
            $example = $this->evaluateTypesafeExample(substr($markdown, $start));

            $markdown = substr($markdown, 0, $start)
                ."```json title=\"{$example['title']}\" theme={null}\n{$example['code']}\n```"
                .substr($markdown, $start + $example['length']);
        }

        return $markdown;
    }

    /**
     * Append `{#slug}` to every heading outside code fences that does not
     * carry an explicit anchor yet.
     */
    public function addHeadingAnchors(string $markdown): string
    {
        $insideFence = false;

        return collect(explode("\n", $markdown))
            ->map(function (string $line) use (&$insideFence) {
                if (str_starts_with($line, '```')) {
                    $insideFence = ! $insideFence;
                }

                if ($insideFence || str_contains($line, '{#')) {
                    return $line;
                }

                if (preg_match('/^(>\s*)?(#{1,6})\s+(.+?)\s*$/', $line, $matches) !== 1) {
                    return $line;
                }

                return "{$matches[1]}{$matches[2]} {$matches[3]} {#".Str::slug($matches[3]).'}';
            })
            ->implode("\n");
    }

    /**
     * Evaluate one `<TypesafeExample ... />` call at the start of `$source`
     * and report the code it displays, its title, and how many characters of
     * `$source` the element spans.
     *
     * The example is a JavaScript object literal (single quotes, trailing
     * commas, comments), so it is evaluated by Node in an empty context
     * rather than parsed as JSON.
     *
     * @return array{code: string, title: string, length: int}
     */
    private function evaluateTypesafeExample(string $source): array
    {
        $result = Process::input($source)->run(['node', '-e', self::NODE_SCRIPT]);

        if ($result->failed()) {
            throw new RuntimeException('Could not evaluate <TypesafeExample>: '.trim($result->errorOutput()));
        }

        return json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);
    }

    private const NODE_SCRIPT = <<<'JS'
        const vm = require('vm');
        const s = require('fs').readFileSync(0, 'utf8');
        const tag = '<TypesafeExample';
        let i = tag.length;

        const skipString = (quote) => {
            i++;
            while (s[i] !== quote) {
                if (s[i] === '\\') i++;
                if (quote === '`' && s[i] === '$' && s[i + 1] === '{') {
                    i += 2;
                    for (let depth = 1; depth; i++) {
                        if (s[i] === '{') depth++;
                        else if (s[i] === '}') depth--;
                    }
                    continue;
                }
                i++;
            }
            i++;
        };

        const props = {};
        for (;;) {
            while (/\s/.test(s[i])) i++;
            if (s[i] === '/' || s[i] === '>') break;
            let name = '';
            while (/[\w-]/.test(s[i])) name += s[i++];
            if (s[i] !== '=') {
                props[name] = true;
                continue;
            }
            i++;
            if (s[i] === '"' || s[i] === "'") {
                const from = i;
                skipString(s[i]);
                props[name] = s.slice(from + 1, i - 1);
            } else if (s[i] === '{') {
                const from = i + 1;
                for (let depth = 0; ; ) {
                    const c = s[i];
                    if (c === '"' || c === "'" || c === '`') { skipString(c); continue; }
                    if (c === '/' && s[i + 1] === '/') { while (s[i] !== '\n') i++; continue; }
                    if (c === '/' && s[i + 1] === '*') { i = s.indexOf('*/', i) + 2; continue; }
                    if (c === '{') depth++;
                    if (c === '}' && --depth === 0) break;
                    i++;
                }
                props[name] = vm.runInNewContext('(' + s.slice(from, i) + ')', Object.create(null), { timeout: 1000 });
                i++;
            }
        }
        const length = s.indexOf('/>', i) + 2;

        const example = props.example;
        const shown = props.display === 'questions'
            ? example.questions
            : example.state === undefined
                ? { questions: example.questions }
                : { state: example.state, questions: example.questions };

        process.stdout.write(JSON.stringify({
            code: JSON.stringify(shown, null, 2),
            title: props.title ?? 'request',
            length,
        }));
        JS;
}
