<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notification;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;

class BlueskyChannel
{
    /**
     * @throws RequestException
     * @throws AuthenticationException
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        /**
         * @var Post $post
         * @phpstan-ignore-next-line
         */
        $post = $notification->toBluesky($notifiable);

        if (! $post instanceof Post) {
            return; // @codeCoverageIgnore
        }

        /** @var BlueskyRoute $route */
        $route = $notifiable->routeNotificationFor('bluesky', $notification);

        if (! $route instanceof BlueskyRoute) {
            return; // @codeCoverageIgnore
        }

        match (true) {
            $route->isOAuth() => Bluesky::withToken($route->oauth)
                ->refreshSession()
                ->post($post)
                ->throw(),
            $route->isLegacy() => Bluesky::login($route->identifier, $route->password)
                ->post($post)
                ->throw(),
        };
    }
}
