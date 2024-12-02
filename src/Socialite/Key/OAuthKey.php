<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Key;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use phpseclib3\Crypt\Common\PrivateKey as CommonPrivateKey;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\EC\PublicKey;

final class OAuthKey
{
    protected const CURVE = 'secp256r1';

    public const TYPE = 'PKCS8';

    protected PrivateKey|CommonPrivateKey $pk;

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

        $self->pk = EC::loadPrivateKey(JWT::urlsafeB64Decode($key));

        return $self;
    }

    public static function create(): self
    {
        $self = new self();

        $self->pk = EC::createKey(self::CURVE);

        return $self;
    }

    public function privateKey(): PrivateKey
    {
        return $this->pk;
    }

    public function privatePEM(): string
    {
        return $this->pk->toString(self::TYPE);
    }

    public function privateB64(): string
    {
        return JWT::urlsafeB64Encode($this->privatePEM());
    }

    public function publicPEM(): string
    {
        return $this->pk->getPublicKey()->toString(self::TYPE);
    }

    public function publicKey(): PublicKey
    {
        return $this->pk->getPublicKey();
    }

    public function toJWK(): JsonWebKey
    {
        return new JsonWebKey($this->privateKey());
    }
}
