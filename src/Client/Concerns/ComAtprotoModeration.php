<?php

/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Moderation;

trait ComAtprotoModeration
{
    public function createReport(string $reasonType, array $subject, ?string $reason = null): Response
    {
        return $this->call(
            api: Moderation::createReport,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
