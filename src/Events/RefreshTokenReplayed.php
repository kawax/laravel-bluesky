<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Client\Response;
use Revolution\Bluesky\Session\OAuthSession;

/**
 * When refresh_token is used twice.
 */
class RefreshTokenReplayed
{
    use Dispatchable;

    public function __construct(
        public OAuthSession $session,
        public Response $response,
    ) {
        //
    }
}
