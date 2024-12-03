<?php

namespace Revolution\Bluesky\Crypto;

use Firebase\JWT\JWT;
use phpseclib3\Crypt\Common\PrivateKey as CommonPrivateKey;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\EC\PublicKey;
use Revolution\Bluesky\Socialite\Key\JsonWebKey;

abstract class AbstractKeypair
{
    protected const CURVE = '';

    public const ALG = '';

    protected const FORMAT = 'PKCS8';

    protected PrivateKey|CommonPrivateKey $key;

    /**
     * @param  string  $key  url-safe base64 encoded private key
     */
    public static function load(string $key): static
    {
        $self = new static();

        $self->key = EC::loadPrivateKey(JWT::urlsafeB64Decode($key));

        return $self;
    }

    public static function create(): static
    {
        $self = new static();

        $self->key = EC::createKey(static::CURVE);

        return $self;
    }

    public function privateKey(): PrivateKey
    {
        return $this->key;
    }

    public function privatePEM(): string
    {
        return $this->key->toString(static::FORMAT);
    }

    public function privateB64(): string
    {
        return JWT::urlsafeB64Encode($this->privatePEM());
    }

    public function publicPEM(): string
    {
        return $this->key->getPublicKey()->toString(static::FORMAT);
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
