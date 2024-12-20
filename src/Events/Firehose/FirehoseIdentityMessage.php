<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events\Firehose;

class FirehoseIdentityMessage
{
    public function __construct(
        public string $did,
        public int $seq,
        public string $time,
        public string $handle,
        public string $raw,
    ) {
        //
    }
}
