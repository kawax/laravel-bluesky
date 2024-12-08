<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Notifications;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Support\Identity;
use RuntimeException;

class BlueskyPrivateChannel
{
    /**
     * @throws RequestException
     * @throws AuthenticationException
     */
    public function send(mixed $notifiable, Notification $notification): ?Response
    {
        /**
         * @var BlueskyPrivateMessage $message
         */
        $message = $notification->toBlueskyPrivate($notifiable);

        // @phpstan-ignore-next-line
        if (! $message instanceof BlueskyPrivateMessage) {
            return null; // @codeCoverageIgnore
        }

        /** @var BlueskyRoute $route */
        $route = $notifiable->routeNotificationFor('bluesky-private', $notification);

        // @phpstan-ignore-next-line
        if (! $route instanceof BlueskyRoute || empty($route->receiver)) {
            return null; // @codeCoverageIgnore
        }

        $id = match (true) {
            $route->isOAuth() => $this->oauth($route),
            $route->isLegacy() => $this->legacy($route),
            default => throw new RuntimeException(),
        };

        return Bluesky::client(auth: true)
            ->chat()
            ->sendMessage($id, $message->toArray());
    }

    protected function oauth(BlueskyRoute $route): string
    {
        $response = Bluesky::withToken($route->oauth)
            ->refreshSession()
            ->client(auth: true)
            ->chat()
            ->getConvoForMembers(Arr::wrap($this->resolveHandle($route->receiver)));

        return $response->json('convo.id') ?? '';
    }

    protected function legacy(BlueskyRoute $route): string
    {
        $response = Bluesky::login($route->identifier, $route->password)
            ->client(auth: true)
            ->chat()
            ->getConvoForMembers(Arr::wrap($this->resolveHandle($route->receiver)));

        return $response->json('convo.id') ?? '';
    }

    /**
     * @param  string  $receiver  DID or handle
     * @return string DID
     */
    protected function resolveHandle(string $receiver): string
    {
        if (Identity::isDID($receiver)) {
            return $receiver;
        }

        if (Identity::isHandle($receiver)) {
            $res = Bluesky::resolveHandle($receiver);
            if ($res->successful()) {
                return $res->json('did');
            }
        }

        return $receiver;
    }
}
