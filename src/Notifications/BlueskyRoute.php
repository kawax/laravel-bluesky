<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\Bluesky\Session\OAuthSession;

final readonly class BlueskyRoute
{
    /**
     * @param  OAuthSession|null  $oauth
     * @param  string|null  $identifier
     * @param  string|null  $password
     * @param  string|null  $receiver  Receiver DID when using PrivateChannel
     */
    public function __construct(
        #[\SensitiveParameter] public ?OAuthSession $oauth = null,
        public ?string $identifier = null,
        #[\SensitiveParameter] public ?string $password = null,
        #[Format('did')] public ?string $receiver = null,
    ) {
    }

    /**
     * @param  OAuthSession|null  $oauth
     * @param  string|null  $identifier
     * @param  string|null  $password
     * @param  string|null  $receiver  Receiver DID when using PrivateChannel
     */
    public static function to(
        #[\SensitiveParameter] ?OAuthSession $oauth = null,
        ?string $identifier = null,
        #[\SensitiveParameter] ?string $password = null,
        #[Format('did')] ?string $receiver = null,
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
