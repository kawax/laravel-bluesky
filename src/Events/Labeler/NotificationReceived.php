<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events\Labeler;

class NotificationReceived
{
    public function __construct(
        public string $reason,
        public array $notification,
    ) {
    }
}
