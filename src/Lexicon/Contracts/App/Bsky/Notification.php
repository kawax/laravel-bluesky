<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\App\Bsky;

interface Notification
{
    public const getUnreadCount = 'app.bsky.notification.getUnreadCount';
    public const listNotifications = 'app.bsky.notification.listNotifications';
    public const putPreferences = 'app.bsky.notification.putPreferences';
    public const registerPush = 'app.bsky.notification.registerPush';
    public const updateSeen = 'app.bsky.notification.updateSeen';

    /**
     * Count the number of unread notifications for the requesting account. Requires auth.
     *
     * method: get
     */
    public function getUnreadCount(?bool $priority = null, ?string $seenAt = null);

    /**
     * Enumerate notifications for the requesting account. Requires auth.
     *
     * method: get
     */
    public function listNotifications(?int $limit = 50, ?bool $priority = null, ?string $cursor = null, ?string $seenAt = null);

    /**
     * Set notification-related preferences for an account. Requires auth.
     *
     * method: post
     */
    public function putPreferences(bool $priority);

    /**
     * Register to receive push notifications, via a specified service, for the requesting account. Requires auth.
     *
     * method: post
     */
    public function registerPush(string $serviceDid, string $token, string $platform, string $appId);

    /**
     * Notify server that the requesting account has seen notifications. Requires auth.
     *
     * method: post
     */
    public function updateSeen(string $seenAt);
}
