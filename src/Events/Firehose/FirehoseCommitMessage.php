<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Events\Firehose;

class FirehoseCommitMessage
{
    public function __construct(
        public string $did,
        public string $action,
        public string $time,
        public ?string $cid,
        public string $path,
        public array $record,
        public array $payload,
        public string $raw,
    ) {
        //
    }
}
