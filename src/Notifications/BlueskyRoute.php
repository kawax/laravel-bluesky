<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

class BlueskyRoute
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $password,
        public readonly string $service = 'https://bsky.social',
    )
    {
    }

    public static function to(string $identifier, string $password, string $service = 'https://bsky.social'): static
    {
        return new static(identifier: $identifier, password: $password, service: $service);
    }
}
