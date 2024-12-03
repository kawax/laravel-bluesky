<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Key;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use phpseclib3\Crypt\Common\PrivateKey as CommonPrivateKey;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\EC\PublicKey;

/**
 * The key used for OAuth. Curve is secp256r1/P-256/ES256.
 */
final class OAuthKey
{
    protected const CURVE = 'secp256r1';

    public const FORMAT = 'PKCS8';

    protected PrivateKey|CommonPrivateKey $key;

    /**
     * @param  string|null  $key  url-safe base64 encoded private key
     */
    public static function load(?string $key = null): self
    {
        if (empty($key)) {
            $key = config('bluesky.oauth.private_key');
        }

        if (empty($key)) {
            throw new InvalidArgumentException('Private key not configured');
        }

        $self = new self();

        $self->key = EC::loadPrivateKey(JWT::urlsafeB64Decode($key));

        return $self;
    }

    public static function create(): self
    {
        $self = new self();

        $self->key = EC::createKey(self::CURVE);

        return $self;
    }

    public function privateKey(): PrivateKey
    {
        return $this->key;
    }

    public function privatePEM(): string
    {
        return $this->key->toString(self::FORMAT);
    }

    public function privateB64(): string
    {
        return JWT::urlsafeB64Encode($this->privatePEM());
    }

    public function publicPEM(): string
    {
        return $this->key->getPublicKey()->toString(self::FORMAT);
    }

    public function publicKey(): PublicKey
    {
        return $this->key->getPublicKey();
    }

    public function toJWK(): JsonWebKey
    {
        return new JsonWebKey($this->privateKey());
    }
}
