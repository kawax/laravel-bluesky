<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Crypto;

use Firebase\JWT\JWT;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\Formats\Keys\PKCS8;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\EC\PublicKey;

abstract class AbstractKeypair
{
    public const CURVE = '';

    public const ALG = '';

    protected PrivateKey $key;

    final public function __construct()
    {
    }

    /**
     * @param  string  $key  url-safe base64 encoded private key
     */
    public static function load(string $key): static
    {
        $self = new static();

        /** @var PrivateKey $sk */
        $sk = EC::loadPrivateKey(JWT::urlsafeB64Decode($key));

        $self->key = $sk;

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
        return $this->key->toString(class_basename(PKCS8::class));
    }

    public function privateB64(): string
    {
        return JWT::urlsafeB64Encode($this->privatePEM());
    }

    public function publicKey(): PublicKey
    {
        return $this->key->getPublicKey();
    }

    public function publicPEM(): string
    {
        return $this->publicKey()->toString(class_basename(PKCS8::class));
    }

    public function toJWK(): JsonWebKey
    {
        return new JsonWebKey($this->privateKey());
    }
}
