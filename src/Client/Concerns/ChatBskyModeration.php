<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Chat\Bsky\Moderation;
use Revolution\Bluesky\Client\HasHttp;

trait ChatBskyModeration
{
    use HasHttp;

    public function getActorMetadata(string $actor): Response
    {
        return $this->call(
            api: Moderation::getActorMetadata,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getMessageContext(string $messageId, ?string $convoId = null, ?int $before = 5, ?int $after = 5): Response
    {
        return $this->call(
            api: Moderation::getMessageContext,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateActorAccess(string $actor, bool $allowAccess, ?string $ref = null): Response
    {
        return $this->call(
            api: Moderation::updateActorAccess,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
