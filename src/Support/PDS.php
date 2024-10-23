<?php

declare(strict_types=1);

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
}
