<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Label;
use Revolution\Bluesky\Client\HasHttp;

trait ComAtprotoLabel
{
    use HasHttp;

    public function queryLabels(array $uriPatterns, ?array $sources = null, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Label::queryLabels,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }
}
