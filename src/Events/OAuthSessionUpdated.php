<?php

namespace Revolution\Bluesky\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Revolution\Bluesky\Session\OAuthSession;

class OAuthSessionUpdated
{
    use Dispatchable;

    public function __construct(
        public OAuthSession $session,
    ) {
        //
    }
}
