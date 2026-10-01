<?php

namespace App\Actions;

class SplitMarkdownForTranslation
{
    /**
     * Estimated output tokens per chunk. A synchronous Bedrock call returns
     * nothing until generation finishes and times out at 300 seconds, i.e.
     * roughly 19,000 tokens at ~65 tokens/second; this keeps each chunk
     * well inside that.
     */
    public const MAX_CHUNK_TOKENS = 8000;

    private const PROSE_TOKENS_PER_CHAR = 0.54;

    private const CODE_CHARS_PER_TOKEN = 3.5;

    /**
     * Split Markdown into chunks small enough to translate in one request,
     * cutting before a heading where possible, else at a blank line, else
     * (inside a long code block) at a blank line within it. Chunks are cut
     * on line boundaries, so a chunk may begin or end inside a fenced code
     * block.
     *
     * @return list<array{text: string, separator: string}> Each chunk with the text
     *                                                      that rejoins it to the next
     *                                                      one (the newlines trimmed
     *                                                      from its end, plus the one
     *                                                      that ended its last line).
     */
    public function __invoke(string $markdown, int $maxTokens = self::MAX_CHUNK_TOKENS): array
    {
        $lines = explode("\n", $markdown);
        $chunks = [];
        $chunkStart = 0;
        $cost = 0.0;
        $fence = null;
        /** @var array{index: int, rank: int}|null $best */
        $best = null;

        foreach ($lines as $index => $line) {
            $inFenceBefore = $fence !== null;
            $fence = $this->fenceAfter($fence, $line);
            $lineCost = $this->tokenCost($line, $inFenceBefore || $fence !== null);

            if ($index > $chunkStart) {
                $rank = $this->boundaryRank($lines, $index, $inFenceBefore);

                if ($rank > 0 && $cost >= $maxTokens * 0.3 && ($best === null || $rank >= $best['rank'])) {
                    $best = ['index' => $index, 'rank' => $rank];
                }
            }

            if ($cost + $lineCost > $maxTokens && $index > $chunkStart) {
                $cut = $best ?? ['index' => $index, 'rank' => 0];

                $chunks[] = $this->chunk($lines, $chunkStart, $cut['index']);
                $chunkStart = $cut['index'];
                $cost = $this->costBetween($lines, $cut['index'], $index);
                $best = null;
            }

            $cost += $lineCost;
        }

        $chunks[] = ['text' => implode("\n", array_slice($lines, $chunkStart)), 'separator' => ''];

        return $chunks;
    }

    /**
     * Rejoin translated chunks with the separators the split recorded.
     *
     * @param  list<array{text: string, separator: string}>  $chunks  The split chunks (source text).
     * @param  list<string>  $translated  The translation of each chunk, in order.
     */
    public function join(array $chunks, array $translated): string
    {
        $result = '';

        foreach ($translated as $index => $text) {
            $result .= $index === count($translated) - 1
                ? $text
                : rtrim($text, "\n").$chunks[$index]['separator'];
        }

        return $result;
    }

    /**
     * @param  list<string>  $lines
     * @return array{text: string, separator: string}
     */
    private function chunk(array $lines, int $start, int $end): array
    {
        $raw = implode("\n", array_slice($lines, $start, $end - $start));
        $text = rtrim($raw, "\n");

        return ['text' => $text, 'separator' => substr($raw, strlen($text))."\n"];
    }

    /**
     * How good a place a cut before the given line is: a heading outside a
     * code block (3), a line after a blank one outside a code block (2), or
     * a line after a blank one inside a code block (1); 0 for no cut.
     *
     * @param  list<string>  $lines
     */
    private function boundaryRank(array $lines, int $index, bool $inFence): int
    {
        if (! $inFence && preg_match('/^#{1,6}\s/', $lines[$index]) === 1) {
            return 3;
        }

        if (trim($lines[$index - 1]) !== '' || trim($lines[$index]) === '') {
            return 0;
        }

        return $inFence ? 1 : 2;
    }

    /**
     * Whether a fenced code block is open after the given line, returning
     * the opening fence (so only a matching one closes it) or null.
     */
    private function fenceAfter(?string $fence, string $line): ?string
    {
        if (preg_match('/^[ \t]*(`{3,}|~{3,})/', $line, $matches) !== 1) {
            return $fence;
        }

        if ($fence === null) {
            return $matches[1];
        }

        return $matches[1][0] === $fence[0] && strlen($matches[1]) >= strlen($fence)
            && preg_match('/^[ \t]*(`{3,}|~{3,})[ \t]*$/', $line) === 1
            ? null
            : $fence;
    }

    private function tokenCost(string $line, bool $isCode): float
    {
        $length = mb_strlen($line) + 1;

        return $isCode
            ? $length / self::CODE_CHARS_PER_TOKEN
            : $length * self::PROSE_TOKENS_PER_CHAR;
    }

    /**
     * Cost of the lines from $from up to (not including) $to, using the
     * same code/prose distinction as the main pass.
     *
     * @param  list<string>  $lines
     */
    private function costBetween(array $lines, int $from, int $to): float
    {
        $cost = 0.0;
        $fence = null;

        foreach (array_slice($lines, 0, $to) as $index => $line) {
            $inFenceBefore = $fence !== null;
            $fence = $this->fenceAfter($fence, $line);

            if ($index >= $from) {
                $cost += $this->tokenCost($line, $inFenceBefore || $fence !== null);
            }
        }

        return $cost;
    }
}
