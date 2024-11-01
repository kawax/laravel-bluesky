<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Embed;

use Illuminate\Contracts\Support\Arrayable;
use Revolution\Bluesky\Enums\Bsky;

final class Images implements Arrayable
{
    private array $images = [];

    public function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function add(string $alt, array|callable $blob): self
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
            '$type' => Bsky::Images->value,
            'images' => collect($this->images)->take(4)->toArray(),
        ];
    }
}
