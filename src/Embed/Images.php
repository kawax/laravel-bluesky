<?php

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\Bluesky\Enums\AtProto;

class Images implements Arrayable
{
    private array $images = [];

    public function __construct()
    {
    }

    public static function create(): static
    {
        return new static();
    }

    public function add(string $alt, array|callable $blob): static
    {
        $this->images[] = [
            'image' => is_callable($blob) ? call_user_func($blob) : $blob,
            'alt' => $alt,
        ];

        return $this;
    }

    public function toArray(): array
    {
        return [
            '$type' => AtProto::Images->value,
            'images' => collect($this->images)->take(4)->toArray(),
        ];
    }
}
