<?php

namespace App\Actions;

use Illuminate\Support\Facades\URL;

class SignImageUrls
{
    /**
     * Matches the unsigned "/images/{path}" URLs stored in Markdown. The
     * path stops at characters that end a Markdown or HTML URL, and the
     * leading lookbehind skips URLs on other hosts.
     */
    private const PATTERN = '#(?<![\w/.:-])/images/([^"\'\s)\]>?\#]+)#';

    /**
     * Replace every stored "/images/{path}" URL in the Markdown with a
     * relative, temporarily signed URL.
     */
    public function __invoke(string $markdown): string
    {
        return preg_replace_callback(
            self::PATTERN,
            fn (array $match) => URL::temporarySignedRoute(
                'images.show',
                now()->addMinutes((int) config('images.signed_url_minutes')),
                ['path' => $match[1]],
                absolute: false,
            ),
            $markdown,
        ) ?? $markdown;
    }

    /**
     * The storage paths of every image referenced from the Markdown.
     *
     * @return list<string>
     */
    public function paths(string $markdown): array
    {
        preg_match_all(self::PATTERN, $markdown, $matches);

        return array_values(array_filter(
            array_unique($matches[1]),
            fn (string $path) => ! str_contains($path, '..'),
        ));
    }
}
