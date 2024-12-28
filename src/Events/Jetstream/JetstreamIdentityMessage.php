<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events\Jetstream;

class JetstreamIdentityMessage
{
    public function __construct(
        public string $kind,
        public array $message,
        public string $host,
        public array $payload,
    ) {
        //
    }
}
