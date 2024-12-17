<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events;

use Illuminate\Foundation\Events\Dispatchable;

class FirehoseMessageReceived
{
    use Dispatchable;

    public function __construct(
        public string $did,
        public string $kind,
        public string $action,
        public string $cid,
        public array $record,
        public array $payload,
        public string $host,
        public string $raw,
    ) {
        //
    }
}
