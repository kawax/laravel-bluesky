<?php

namespace Revolution\Bluesky\Support;

use Illuminate\Support\Str;

final class DID
{
    /**
     * Get `did:web:example.com` format DID from input or current url.
     */
    public static function web(?string $url = null): string
    {
        return 'did:web:'.Str::of($url ?? url('/'))->explode('/', 3)->get(2);
    }
}
