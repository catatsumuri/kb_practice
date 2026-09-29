<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesFetchableUrls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class OgpController extends Controller
{
    use ValidatesFetchableUrls;

    /**
     * Proxy an Open Graph metadata lookup for a link so inkstream's
     * LinkCard can show a preview without the browser hitting third-party
     * sites directly (which OGP-bearing sites generally don't allow via
     * CORS). Always answers with JSON, even on a validation failure —
     * this is called from a plain `fetch()`, not an Inertia form, so it
     * can't rely on Laravel's default redirect-on-invalid behavior.
     */
    public function fetch(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'url' => ['required', 'url:http,https', 'max:2048'],
            ]);

            $this->assertUrlIsFetchable($validated['url'], 'url');
        } catch (ValidationException) {
            return $this->emptyResponse(422);
        }

        $url = $validated['url'];

        $metadata = Cache::remember(
            'ogp:'.md5($url),
            now()->addDay(),
            fn () => $this->fetchOpenGraphTags($url),
        );

        if ($metadata === null) {
            return $this->emptyResponse(502);
        }

        return response()->json($metadata);
    }

    /**
     * @return array{title: ?string, description: ?string, image: ?string}|null
     */
    private function fetchOpenGraphTags(string $url): ?array
    {
        try {
            $html = Http::connectTimeout(5)
                ->timeout(10)
                ->withoutRedirecting()
                ->get($url)
                ->throw()
                ->body();
        } catch (\Throwable) {
            return null;
        }

        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);

        $property = function (string $name) use ($xpath): ?string {
            $node = $xpath->query("//meta[@property='{$name}']/@content")->item(0);

            return $node?->nodeValue;
        };

        return [
            'title' => $property('og:title'),
            'description' => $property('og:description'),
            'image' => $property('og:image'),
        ];
    }

    private function emptyResponse(int $status): JsonResponse
    {
        return response()->json(['title' => null, 'description' => null, 'image' => null], $status);
    }
}
