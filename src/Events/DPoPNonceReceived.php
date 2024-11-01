<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Revolution\Bluesky\Session\OAuthSession;

/**
 * DPoP-Nonce received.
 */
class DPoPNonceReceived
{
    use Dispatchable;

    public function __construct(
        public string $nonce,
        public OAuthSession $session,
    ) {
        //
    }
}
