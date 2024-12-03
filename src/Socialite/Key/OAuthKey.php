<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Key;

use InvalidArgumentException;
use Revolution\Bluesky\Crypto\P256;

/**
 * The key used for OAuth. Curve is secp256r1/P-256/ES256.
 */
class OAuthKey extends P256
{
    /**
     * @param  string|null  $key  url-safe base64 encoded private key
     */
    #[\Override]
    public static function load(?string $key = null): static
    {
        if (empty($key)) {
            $key = config('bluesky.oauth.private_key');
        }

        if (empty($key)) {
            throw new InvalidArgumentException('Private key not configured');
        }

        return parent::load($key);
    }
}
