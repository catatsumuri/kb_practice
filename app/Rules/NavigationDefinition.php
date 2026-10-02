<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NavigationDefinition implements ValidationRule
{
    public const MAX_BYTES = 65536;

    public const MAX_DEPTH = 5;

    private const NODE_KEYS = ['title', 'label', 'page', 'pages'];

    /**
     * Validate that the value is a JSON navigation tree: a list of document
     * paths or nodes with optional title, label, page, and nested pages.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if (strlen($value) > self::MAX_BYTES) {
            $fail('ナビゲーションは'.(self::MAX_BYTES / 1024).'KB以内で入力してください。');

            return;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail('ナビゲーションは正しいJSONで入力してください（'.json_last_error_msg().'）。');

            return;
        }

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            $fail('ナビゲーションは配列で入力してください。');

            return;
        }

        $error = $this->validateEntries($decoded, 'ナビゲーション', 1);

        if ($error !== null) {
            $fail($error);
        }
    }

    /**
     * @param  array<int, mixed>  $entries
     */
    private function validateEntries(array $entries, string $location, int $depth): ?string
    {
        if ($depth > self::MAX_DEPTH) {
            return $location.'の入れ子は'.self::MAX_DEPTH.'階層までです。';
        }

        foreach ($entries as $index => $entry) {
            $error = $this->validateEntry($entry, $location.'の'.($index + 1).'番目', $depth);

            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    private function validateEntry(mixed $entry, string $location, int $depth): ?string
    {
        if (is_string($entry)) {
            return trim($entry) === '' ? $location.'のパスが空です。' : null;
        }

        if (! is_array($entry) || array_is_list($entry)) {
            return $location.'は文字列かオブジェクトで指定してください。';
        }

        $unknownKeys = array_diff(array_keys($entry), self::NODE_KEYS);

        if ($unknownKeys !== []) {
            return $location.'に未対応のキーがあります: '.implode(', ', $unknownKeys);
        }

        foreach (['title', 'label', 'page'] as $key) {
            if (isset($entry[$key]) && (! is_string($entry[$key]) || trim($entry[$key]) === '')) {
                return $location.'の'.$key.'は空でない文字列で指定してください。';
            }
        }

        if (! isset($entry['pages'])) {
            return null;
        }

        if (! is_array($entry['pages']) || ! array_is_list($entry['pages'])) {
            return $location.'のpagesは配列で指定してください。';
        }

        return $this->validateEntries($entry['pages'], $location.'のpages', $depth + 1);
    }
}
