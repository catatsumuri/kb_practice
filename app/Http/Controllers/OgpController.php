<?php

namespace App\Http\Controllers;

use Closure;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OgpController extends Controller
{
    /**
     * Fetch OGP metadata for a URL referenced by a rendered document's
     * link-card embed. Ported from thinkstream v1's OgpController /
     * OgpMetadataService, since inkstream's link card is unstyled and
     * expects its consumer to provide this endpoint (a browser fetch to
     * an arbitrary origin would hit CORS).
     */
    public function fetch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => [
                'required',
                'url:http,https',
                'max:2048',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! $this->isAllowedUrl($value)) {
                        $fail("The {$attribute} field is invalid.");
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid URL',
                'messages' => $validator->errors(),
            ], 422);
        }

        $url = $request->string('url')->toString();

        $metadata = Cache::remember('ogp:'.md5($url), now()->addHours(24), function () use ($url) {
            return $this->fetchMetadata($url);
        });

        if (! $metadata) {
            return response()->json(['error' => 'Failed to fetch OGP metadata'], 404);
        }

        return response()->json($metadata);
    }

    /**
     * Determine whether the service may fetch metadata for the given URL,
     * rejecting localhost and private/reserved IP ranges to guard against
     * SSRF via redirected or attacker-controlled hosts.
     */
    private function isAllowedUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($scheme) || ! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        if (! is_string($host) || $host === '') {
            return false;
        }

        if ($this->isBlockedHost($host)) {
            return false;
        }

        foreach ($this->resolveHostAddresses($host) as $address) {
            if ($this->isBlockedHost($address)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function resolveHostAddresses(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        return collect($records)
            ->map(fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null)
            ->filter(fn (?string $address): bool => is_string($address) && $address !== '')
            ->values()
            ->all();
    }

    private function isBlockedHost(string $host): bool
    {
        $normalizedHost = Str::lower(trim($host, '[]'));

        if (in_array($normalizedHost, ['localhost', 'localhost.localdomain'], true)) {
            return true;
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return filter_var(
            $normalizedHost,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }

    /**
     * @return array{title: string, description: ?string, image: ?string, url: string}|null
     */
    private function fetchMetadata(string $url): ?array
    {
        try {
            $response = Http::connectTimeout(5)
                ->retry([200, 500, 1000], throw: false)
                ->timeout(10)
                ->withoutRedirecting()
                ->withUserAgent('Mozilla/5.0 (compatible; OGP-Fetcher/1.0)')
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $dom = $this->parseHtml($response->body());

            if ($dom === null) {
                return null;
            }

            $xpath = new DOMXPath($dom);

            $title = $this->extractOgpTag($xpath, 'og:title')
                ?? $this->extractTitle($xpath);

            if (empty($title)) {
                return null;
            }

            return [
                'title' => $title,
                'description' => $this->extractOgpTag($xpath, 'og:description')
                    ?? $this->extractMetaTag($xpath, 'description'),
                'image' => $this->extractOgpTag($xpath, 'og:image'),
                'url' => $url,
            ];
        } catch (ConnectionException $e) {
            Log::warning('OGP fetch failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function parseHtml(string $html): ?DOMDocument
    {
        $dom = new DOMDocument;

        libxml_use_internal_errors(true);
        $result = $dom->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR);
        libxml_clear_errors();

        return $result ? $dom : null;
    }

    private function extractOgpTag(DOMXPath $xpath, string $property): ?string
    {
        $nodes = $xpath->query("//meta[@property='{$property}']/@content");

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->nodeValue);

        return $value !== '' ? $value : null;
    }

    private function extractMetaTag(DOMXPath $xpath, string $name): ?string
    {
        $nodes = $xpath->query("//meta[@name='{$name}']/@content");

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->nodeValue);

        return $value !== '' ? $value : null;
    }

    private function extractTitle(DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//title');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->textContent);

        return $value !== '' ? $value : null;
    }
}
