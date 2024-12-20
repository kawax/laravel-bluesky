<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events\Firehose;

class FirehoseMessageReceived
{
    public function __construct(
        public array $header,
        public array $payload,
        public string $raw,
    ) {
        //
    }
}
