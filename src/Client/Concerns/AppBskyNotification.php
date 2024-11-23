<?php
/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Notification;

trait AppBskyNotification
{
    public function getUnreadCount(?bool $priority = null, ?string $seenAt = null): Response
    {
        return $this->call(
            api: Notification::getUnreadCount,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function listNotifications(?int $limit = 50, ?bool $priority = null, ?string $cursor = null, ?string $seenAt = null): Response
    {
        return $this->call(
            api: Notification::listNotifications,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function putPreferences(bool $priority): Response
    {
        return $this->call(
            api: Notification::putPreferences,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function registerPush(string $serviceDid, string $token, string $platform, string $appId): Response
    {
        return $this->call(
            api: Notification::registerPush,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateSeen(string $seenAt): Response
    {
        return $this->call(
            api: Notification::updateSeen,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
