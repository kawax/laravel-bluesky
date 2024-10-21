<?php

namespace Revolution\Bluesky\Support;

use Illuminate\Support\Facades\Http;

/**
 * Personal Data Server.
 */
class PDS
{
    public function protectedResource($pds_url): array
    {
        return Http::baseUrl($pds_url)
            ->get('/.well-known/oauth-protected-resource')
            ->json();
    }
}
