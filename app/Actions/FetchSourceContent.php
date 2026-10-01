<?php

namespace App\Actions;

use App\Http\Controllers\Concerns\ValidatesFetchableUrls;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FetchSourceContent
{
    use ValidatesFetchableUrls;

    /**
     * Fetch a source URL's content and pull out a title from its first
     * Markdown h1.
     *
     * @return array{content: string, title: ?string}
     *
     * @throws ValidationException When the URL is not fetchable or the request fails.
     */
    public function __invoke(string $url): array
    {
        $this->assertUrlIsFetchable($url, 'source_url');

        try {
            $content = Http::timeout(10)->get($url)->throw()->body();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'source_url' => '指定のURLから本文を取得できませんでした。',
            ]);
        }

        $title = null;

        if (preg_match('/^#\s+(.+)$/m', $content, $matches) === 1) {
            $title = trim($matches[1]);
        }

        return ['content' => $content, 'title' => $title];
    }
}
