<?php

namespace Revolution\Bluesky\Socalite;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\EC\PublicKey;
use Stringable;

class JsonWebKey implements Arrayable, Jsonable, Stringable
{
    public const ALG = 'ES256';

    protected string $kid = 'illuminate';

    public function __construct(protected PrivateKey|PublicKey $key)
    {
        //
    }

    public function key(): PublicKey|PrivateKey
    {
        return $this->key;
    }

    public function kid(): string
    {
        return $this->kid;
    }

    public function withKid(string $kid): static
    {
        $this->kid = $kid;

        return $this;
    }

    public function asPublic(): static
    {
        if ($this->key instanceof PublicKey) {
            return $this;
        }

        $clone = clone $this;
        $clone->key = $this->key->getPublicKey();

        return $clone;
    }

    public function toArray(): array
    {
        return data_get(json_decode($this->key->toString('JWK', [
            'kid' => $this->kid,
            'alg' => self::ALG,
            'use' => 'sig',
        ]), true), 'keys.0', []);
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
