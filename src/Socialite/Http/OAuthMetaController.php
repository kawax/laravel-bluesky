<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Http;

use Revolution\Bluesky\Socialite\OAuthConfig;

class OAuthMetaController
{
    public function clientMetadata(): mixed
    {
        return OAuthConfig::clientMetadata();
    }

    public function jwks(): mixed
    {
        return OAuthConfig::jwks();
    }
}
