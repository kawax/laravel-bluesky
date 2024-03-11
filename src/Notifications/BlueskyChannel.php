<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notification;
use Revolution\Bluesky\Facades\Bluesky;

class BlueskyChannel
{
    /**
     * @throws RequestException
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        /** @var BlueskyMessage $message */
        $message = $notification->toBluesky($notifiable);

        if (! $message instanceof BlueskyMessage) {
            return; // @codeCoverageIgnore
        }

        /** @var BlueskyRoute $route */
        $route = $notifiable->routeNotificationFor('bluesky', $notification);

        if (! $route instanceof BlueskyRoute) {
            return; // @codeCoverageIgnore
        }

        Bluesky::service($route->service)
            ->login($route->identifier, $route->password)
            ->post($message->text)
            ->throw();
    }
}
