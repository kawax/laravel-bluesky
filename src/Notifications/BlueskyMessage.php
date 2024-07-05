<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Illuminate\Contracts\Support\Arrayable;

readonly class BlueskyMessage implements Arrayable
{
    public function __construct(
        public string $text,
    ) {
    }

    public static function create(string $text): static
    {
        return new static(text: $text);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
