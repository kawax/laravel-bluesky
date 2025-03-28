<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Revolution\Bluesky\Session\OAuthSession;

/**
 * When an OAuthSession refresh is starting. refresh_token is empty.
 */
class OAuthSessionRefreshing
{
    use Dispatchable;

    public function __construct(
        public OAuthSession $session,
    ) {
        //
    }
}
