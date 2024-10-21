<?php

namespace Revolution\Bluesky\Socalite\Key;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Stringable;

class JsonWebKeySet implements Arrayable, Jsonable, Stringable
{
    protected array $keys = [];

    public static function load(): static
    {
        $self = new static();

        $key = BlueskyKey::load()->toJWK()->asPublic();

        return $self->addKey($key);
    }

    public function addKey(JsonWebKey $key): static
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
