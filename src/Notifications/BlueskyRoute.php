<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

final readonly class BlueskyRoute
{
    public function __construct(
        public string $identifier,
        #[\SensitiveParameter] public string $password,
        public string $service = 'https://bsky.social',
    ) {
    }

    public static function to(string $identifier, #[\SensitiveParameter] string $password, string $service = 'https://bsky.social'): self
    {
        return new self(...func_get_args());
    }
}
