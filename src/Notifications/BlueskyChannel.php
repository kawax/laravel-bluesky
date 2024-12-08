<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Record\Post;
use RuntimeException;

class BlueskyChannel
{
    /**
     * @throws RequestException
     * @throws AuthenticationException
     */
    public function send(mixed $notifiable, Notification $notification): ?Response
    {
        /**
         * @var Post $post
         */
        $post = $notification->toBluesky($notifiable);

        // @phpstan-ignore-next-line
        if (! $post instanceof Post) {
            return null; // @codeCoverageIgnore
        }

        /** @var BlueskyRoute $route */
        $route = $notifiable->routeNotificationFor('bluesky', $notification);

        // @phpstan-ignore-next-line
        if (! $route instanceof BlueskyRoute) {
            return null; // @codeCoverageIgnore
        }

        return match (true) {
            $route->isOAuth() => Bluesky::withToken($route->oauth)
                ->refreshSession()
                ->post($post),
            $route->isLegacy() => Bluesky::login($route->identifier, $route->password)
                ->post($post),
            default => throw new RuntimeException(),
        };
    }
}
