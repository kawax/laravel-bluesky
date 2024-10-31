<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Revolution\Bluesky\Session\OAuthSession;

class OAuthSessionRefreshing
{
    use Dispatchable;

    public function __construct(
        public OAuthSession $session,
    ) {
        // When an OAuthSession refresh is starting. refresh_token is empty.
    }
}
