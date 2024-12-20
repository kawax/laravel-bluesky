<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events\Firehose;

class FirehoseAccountMessage
{
    public function __construct(
        public string $did,
        public int $seq,
        public string $time,
        public bool $active,
        public ?string $status,
        public string $raw,
    ) {
        //
    }
}
