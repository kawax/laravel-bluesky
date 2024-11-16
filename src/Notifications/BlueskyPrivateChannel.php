<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Revolution\Bluesky\Facades\Bluesky;

class BlueskyPrivateChannel
{
    /**
     * @throws RequestException
     * @throws AuthenticationException
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        /**
         * @var BlueskyPrivateMessage $message
         */
        $message = $notification->toBlueskyPrivate($notifiable);

        if (! $message instanceof BlueskyPrivateMessage) {
            return; // @codeCoverageIgnore
        }

        /** @var BlueskyRoute $route */
        $route = $notifiable->routeNotificationFor('bluesky-private', $notification);

        if (! $route instanceof BlueskyRoute || empty($route->receiver)) {
            return; // @codeCoverageIgnore
        }

        $id = match (true) {
            $route->isOAuth() => $this->oauth($route),
            $route->isLegacy() => $this->legacy($route),
        };

        Bluesky::client(auth: true)
            ->chat()
            ->sendMessage($id, $message->toArray());
    }

    protected function oauth(BlueskyRoute $route): string
    {
        $response = Bluesky::withToken($route->oauth)
            ->refreshSession()
            ->client(auth: true)
            ->chat()
            ->getConvoForMembers(Arr::wrap($route->receiver));

        return $response->json('convo.id') ?? '';
    }

    protected function legacy(BlueskyRoute $route): string
    {
        $response = Bluesky::login($route->identifier, $route->password)
            ->client(auth: true)
            ->chat()
            ->getConvoForMembers(Arr::wrap($route->receiver));

        return $response->json('convo.id') ?? '';
    }
}
