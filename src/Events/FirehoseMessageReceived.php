<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events;

use Illuminate\Foundation\Events\Dispatchable;

class FirehoseMessageReceived
{
    use Dispatchable;

    public function __construct(
        public array $header,
        public array $payload,
        public string $host,
        public string $raw,
    ) {
        //
    }
}
