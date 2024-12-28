<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events\Jetstream;

class JetstreamCommitMessage
{
    public function __construct(
        public string $kind,
        public string $operation,
        public array $message,
        public string $host,
        public array $payload,
    ) {
        //
    }
}
