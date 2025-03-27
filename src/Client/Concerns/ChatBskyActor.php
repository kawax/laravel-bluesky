<?php

/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Chat\Bsky\Actor;

trait ChatBskyActor
{
    public function deleteAccount(): Response
    {
        return $this->call(
            api: Actor::deleteAccount,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function exportAccountData(): Response
    {
        return $this->call(
            api: Actor::exportAccountData,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }
}
