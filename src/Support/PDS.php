<?php

namespace Revolution\Bluesky\Support;

use Illuminate\Support\Facades\Http;

/**
 * Personal Data Server.
 */
class PDS
{
    /**
     * Get PDS OAuth protected resource.
     */
    public function resource(string $pds_url): array
    {
        return Http::baseUrl($pds_url)
            ->get('/.well-known/oauth-protected-resource')
            ->json();
    }

    public function endpoint(?array $meta, ?string $default = null): ?string
    {
        info('pds', $meta);

        return collect($meta['service'] ?? [])
            ->firstWhere('id', '#atproto_pds')['serviceEndpoint'] ?? $default;
    }
}
