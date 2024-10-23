<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socalite\Http;

use Revolution\Bluesky\Socalite\OAuthConfig;

class OAuthMetaController
{
    public function clientMetadata()
    {
        return OAuthConfig::clientMetadata();
    }

    public function jwks()
    {
        return OAuthConfig::jwks();
    }
}
