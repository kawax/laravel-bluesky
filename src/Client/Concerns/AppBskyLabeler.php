<?php

/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Labeler;

trait AppBskyLabeler
{
    public function getServices(array $dids, ?bool $detailed = null): Response
    {
        return $this->call(
            api: Labeler::getServices,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }
}
