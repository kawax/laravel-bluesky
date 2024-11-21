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
     *
     * @link https://morel.us-east.host.bsky.network/.well-known/oauth-protected-resource
     */
    public function getProtectedResource(string $pds_url): ProtectedResource
    {
        return ProtectedResource::make(
            Http::baseUrl($pds_url)
                ->get('/.well-known/oauth-protected-resource')
                ->json(),
        );
    }
}
