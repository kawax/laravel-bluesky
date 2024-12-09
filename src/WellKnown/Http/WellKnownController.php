<?php

declare(strict_types=1);

namespace Revolution\Bluesky\WellKnown\Http;

use Revolution\Bluesky\WellKnown\WellKnownConfig;

class WellKnownController
{
    /**
     * `/.well-known/did.json`.
     */
    public function did(): mixed
    {
        return WellKnownConfig::did();
    }

    /**
     * `/.well-known/atproto-did`.
     */
    public function atproto(): mixed
    {
        return WellKnownConfig::atprotoDid();
    }
}
