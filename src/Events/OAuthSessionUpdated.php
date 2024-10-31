<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Revolution\Bluesky\Session\OAuthSession;

/**
 * When an OAuth2Session is refreshed.
 */
class OAuthSessionUpdated
{
    use Dispatchable;

    public function __construct(
        public OAuthSession $session,
    ) {
        //
    }
}
