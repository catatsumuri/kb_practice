<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Validation\ValidationException;

trait ValidatesFetchableUrls
{
    /**
     * Reject hosts that resolve to private, loopback, or otherwise reserved
     * IP ranges, so this can't be used to probe the server's internal network.
     */
    private function assertUrlIsFetchable(string $url, string $field): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        $ip = $host !== null && filter_var($host, FILTER_VALIDATE_IP)
            ? $host
            : gethostbyname((string) $host);

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw ValidationException::withMessages([
                $field => 'このURLからは取得できません。',
            ]);
        }
    }
}
