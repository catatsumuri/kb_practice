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
        return $this->addHeadingAnchors(
            $this->flattenImages(
                $this->flattenHtmlTables(
                    $this->removeInteractiveWidgets($this->flattenTypesafeExamples($markdown)),
                ),
            ),
        );
    }

    /**
     * Turn raw `<img>` tags (optionally wrapped in a Mintlify `<Frame>`)
     * into Markdown images. Mintlify pairs a light and a dark variant and
     * shows one via Tailwind classes (`block dark:hidden` / `hidden
     * dark:block`); those become `#only-light` / `#only-dark` fragments on
     * the image URL so the stylesheet can pick one per theme. Images still
     * point at the source site's CDN.
     */
    public function flattenImages(string $markdown): string
    {
        $markdown = preg_replace_callback(
            '/^([ \t]*)<Frame\b[^>]*>\s*(.*?)\s*<\/Frame>[ \t]*$/ms',
            fn (array $match) => $this->imagesToMarkdown($match[2], $match[1]),
            $markdown,
        );

        return preg_replace_callback(
            '/^([ \t]*)((?:<img\b[^>]*>[ \t]*)+)$/m',
            fn (array $match) => $this->imagesToMarkdown($match[2], $match[1]),
            $markdown,
        );
    }

    /**
     * Convert every `<img>` in `$html` to a Markdown image, one per
     * paragraph, each line prefixed with `$indent`.
     */
    private function imagesToMarkdown(string $html, string $indent): string
    {
        preg_match_all('/<img\b([^>]*?)\/?>/s', $html, $images);

        return collect($images[1])
            ->map(function (string $attributes) use ($indent) {
                $attribute = fn (string $name) => preg_match('/\b'.$name.'=(?:"([^"]*)"|\{"([^"]*)"\})/', $attributes, $m) === 1
                    ? ($m[1] !== '' ? $m[1] : ($m[2] ?? ''))
                    : '';

                $classes = $attribute('className').' '.$attribute('class');
                $variant = match (true) {
                    str_contains($classes, 'dark:hidden') => '#only-light',
                    str_contains($classes, 'dark:block') => '#only-dark',
                    default => '',
                };

                $alt = str_replace(['[', ']'], '', $attribute('alt'));

                return "{$indent}![{$alt}](".$attribute('src').(str_contains($attribute('src'), '#') ? '' : $variant).')';
            })
            ->implode("\n\n");
    }

    /**
     * Turn each raw `<table>` (written in MDX with JSX attributes such as
     * `colSpan={3}` and `style={{ ... }}`) into a Markdown table. Markdown
     * tables have no colspan, so the labels of a spanning header row are
     * prefixed onto the header row below it ("probabilities: Level 0").
     */
    public function flattenHtmlTables(string $markdown): string
    {
        return preg_replace_callback('/<table\b[^>]*>.*?<\/table>/s', function (array $match) {
            $rows = $this->htmlTableRows($match[0]);
            $headerRows = $rows['header'] === [] ? array_splice($rows['body'], 0, 1) : $rows['header'];

            if ($headerRows === []) {
                return $match[0];
            }

            $header = $this->collapseHeaderRows($headerRows);
            $lines = [
                '| '.implode(' | ', $header).' |',
                '| '.implode(' | ', array_fill(0, count($header), '-')).' |',
            ];

            foreach ($rows['body'] as $row) {
                $lines[] = '| '.implode(' | ', array_column($row, 'text')).' |';
            }

            return implode("\n", $lines);
        }, $markdown);
    }

    /**
     * @return array{header: list<list<array{text: string, span: int}>>, body: list<list<array{text: string, span: int}>>}
     */
    private function htmlTableRows(string $table): array
    {
        $section = fn (string $tag) => preg_match("/<{$tag}\\b[^>]*>(.*?)<\\/{$tag}>/s", $table, $m) === 1 ? $m[1] : '';

        $parse = function (string $html): array {
            preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/s', $html, $rows);

            return array_map(function (string $row): array {
                preg_match_all('/<(th|td)\b([^>]*?)(?:\/>|>(.*?)<\/\1>)/s', $row, $cells, PREG_SET_ORDER);

                return array_map(fn (array $cell) => [
                    'text' => $this->htmlCellText($cell[3] ?? ''),
                    'span' => preg_match('/colSpan=(?:\{|")?(\d+)/i', $cell[2], $span) === 1 ? (int) $span[1] : 1,
                ], $cells);
            }, $rows[1]);
        };

        $hasSections = $section('thead') !== '' || $section('tbody') !== '';

        return [
            'header' => $parse($section('thead')),
            'body' => $parse($hasSections ? $section('tbody') : $table),
        ];
    }

    private function htmlCellText(string $html): string
    {
        $text = preg_replace('/<code\b[^>]*>(.*?)<\/code>/s', '`$1`', $html);
        $text = html_entity_decode(strip_tags($text));

        return str_replace('|', '\\|', trim(preg_replace('/\s+/', ' ', $text)));
    }

    /**
     * Collapse stacked header rows into one, prefixing each column's label
     * with the labels of the spanning cells above it.
     *
     * @param  list<list<array{text: string, span: int}>>  $headerRows
     * @return list<string>
     */
    private function collapseHeaderRows(array $headerRows): array
    {
        $columns = [];

        foreach ($headerRows as $row) {
            $labels = [];

            foreach ($row as $cell) {
                array_push($labels, ...array_fill(0, $cell['span'], $cell['text']));
            }

            foreach ($labels as $index => $label) {
                $columns[$index][] = $label;
            }
        }

        return array_map(
            fn (array $labels) => implode(': ', array_filter($labels, fn (string $label) => $label !== '')),
            $columns,
        );
    }

    /**
     * Drop the interactive explorer widgets (ScoreExplorer,
     * ConfidenceExplorer): both their inline `export function` definition and
     * the `<... />` call. They only work as live React components and have
     * no Markdown equivalent; the surrounding prose and code samples stand on
     * their own.
     */
    public function removeInteractiveWidgets(string $markdown): string
    {
        $markdown = preg_replace('/^export function (?:ScoreExplorer|ConfidenceExplorer)\(.*?^\}\n\n?/ms', '', $markdown);

        return preg_replace('/^<(?:ScoreExplorer|ConfidenceExplorer)\b[^>]*\/>(?:\n\n?|$)/m', '', $markdown);
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

            // A call nested in <Step>/<Accordion> is indented; the block that
            // replaces it must keep that indentation on every line.
            $lineStart = strrpos(substr($markdown, 0, $start), "\n");
            $indent = substr($markdown, $lineStart === false ? 0 : $lineStart + 1, $start - ($lineStart === false ? 0 : $lineStart + 1));
            $indent = trim($indent) === '' ? $indent : '';

            $block = collect([
                "```json title=\"{$example['title']}\" theme={null}",
                ...explode("\n", $example['code']),
                '```',
            ])->implode("\n".$indent);

            $markdown = substr($markdown, 0, $start).$block.substr($markdown, $start + $example['length']);
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
