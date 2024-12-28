<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events\Jetstream;

class JetstreamAccountMessage
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
