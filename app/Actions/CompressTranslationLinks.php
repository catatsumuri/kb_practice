<?php

namespace App\Actions;

use Closure;
use UnexpectedValueException;

class CompressTranslationLinks
{
    private const MAX_URL_LENGTH = 256;

    /**
     * Replace oversized link destinations with short markers for translation.
     * Raw HTML anchors are converted to Markdown links so rendered output
     * remains clickable after their original destinations are restored.
     *
     * @return array{source: string, replacements: array<string, string>}
     */
    public function compress(string $source): array
    {
        $replacements = [];
        $placeholderIndex = 0;

        $placeholderFor = function (string $url) use (&$replacements, &$placeholderIndex, $source): string {
            do {
                $placeholder = "__PRESERVED_URL_{$placeholderIndex}__";
                $placeholderIndex++;
            } while (str_contains($source, $placeholder));

            $replacements[$placeholder] = $url;

            return $placeholder;
        };

        $source = $this->replaceOutsideFencedCodeBlocks($source, function (string $segment) use ($placeholderFor): string {
            $segment = preg_replace_callback(
                '~<a\b(?<attributes>[^>]*)>(?<label>.*?)</a>~is',
                function (array $matches) use ($placeholderFor): string {
                    if (preg_match(
                        '~\bhref\s*=\s*(?:"(?<double>[^"]*)"|\'(?<single>[^\']*)\')~i',
                        $matches['attributes'],
                        $hrefMatches,
                        PREG_UNMATCHED_AS_NULL,
                    ) !== 1) {
                        return $matches[0];
                    }

                    $url = $hrefMatches['double'] ?? $hrefMatches['single'];

                    if ($url === null || mb_strlen($url) <= self::MAX_URL_LENGTH) {
                        return $matches[0];
                    }

                    $label = trim(strip_tags($matches['label']));
                    $label = $label === '' ? 'Link' : $label;
                    $label = str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], $label);

                    return '['.$label.']('.$placeholderFor($url).')';
                },
                $segment,
            ) ?? $segment;

            return preg_replace_callback(
                '~(?<prefix>\]\(\s*<?)(?<url>https?://[^\s)>]+)(?<suffix>>?)~i',
                function (array $matches) use ($placeholderFor): string {
                    if (mb_strlen($matches['url']) <= self::MAX_URL_LENGTH) {
                        return $matches[0];
                    }

                    return $matches['prefix'].$placeholderFor($matches['url']).$matches['suffix'];
                },
                $segment,
            ) ?? $segment;
        });

        return [
            'source' => $source,
            'replacements' => $replacements,
        ];
    }

    /**
     * Restore the original destinations after translation.
     *
     * @param  array<string, string>  $replacements
     *
     * @throws UnexpectedValueException When the translation omits a URL marker.
     */
    public function restore(string $translatedSource, array $replacements): string
    {
        foreach ($replacements as $placeholder => $url) {
            if (! str_contains($translatedSource, $placeholder)) {
                throw new UnexpectedValueException('The translation omitted a preserved link placeholder.');
            }
        }

        return strtr($translatedSource, $replacements);
    }

    private function replaceOutsideFencedCodeBlocks(string $source, Closure $callback): string
    {
        preg_match_all('/^[ \t]*(`{3,}|~{3,})[^\n]*(?:\R.*?^[ \t]*\1[ \t]*$|\R.*\z)/ms', $source, $matches, PREG_OFFSET_CAPTURE);

        if ($matches[0] === []) {
            return $callback($source);
        }

        $result = '';
        $offset = 0;

        foreach ($matches[0] as [$codeBlock, $codeBlockOffset]) {
            $result .= $callback(substr($source, $offset, $codeBlockOffset - $offset));
            $result .= $codeBlock;
            $offset = $codeBlockOffset + strlen($codeBlock);
        }

        return $result.$callback(substr($source, $offset));
    }
}
