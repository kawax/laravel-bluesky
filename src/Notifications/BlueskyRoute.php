<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Revolution\Bluesky\Session\OAuthSession;

final readonly class BlueskyRoute
{
    public function __construct(
        #[\SensitiveParameter] public ?OAuthSession $oauth = null,
        public ?string $identifier = null,
        #[\SensitiveParameter] public ?string $password = null,
    ) {
    }

    public static function to(
        #[\SensitiveParameter] ?OAuthSession $oauth = null,
        ?string $identifier = null,
        #[\SensitiveParameter] ?string $password = null,
    ): self {
        return new self(...func_get_args());
    }

    public function isOAuth(): bool
    {
        return filled($this->oauth);
    }

    public function isLegacy(): bool
    {
        return filled($this->identifier) && filled($this->password);
    }
}
