<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Socialite\Key;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Stringable;

final class JsonWebKeySet implements Arrayable, Jsonable, Stringable
{
    protected array $keys = [];

    public static function load(): self
    {
        $self = new self();

        $key = OAuthKey::load()->toJWK()->asPublic();

        return $self->addKey($key);
    }

    public function addKey(JsonWebKey $key): self
    {
        $this->keys[] = $key;

        return $this;
    }

    public function toArray(): array
    {
        return ['keys' => collect($this->keys)->toArray()];
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
