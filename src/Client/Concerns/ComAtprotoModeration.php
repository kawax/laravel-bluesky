<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Moderation;
use Revolution\Bluesky\Client\HasHttp;

trait ComAtprotoModeration
{
    use HasHttp;

    public function createReport(string $reasonType, array $subject, ?string $reason = null): Response
    {
        return $this->call(
            api: Moderation::createReport,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
