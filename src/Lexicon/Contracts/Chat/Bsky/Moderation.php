<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Chat\Bsky;

interface Moderation
{
    public const getActorMetadata = 'chat.bsky.moderation.getActorMetadata';
    public const getMessageContext = 'chat.bsky.moderation.getMessageContext';
    public const updateActorAccess = 'chat.bsky.moderation.updateActorAccess';

    /**
     * chat.bsky.moderation.getActorMetadata
     *
     * method: get
     */
    public function getActorMetadata(string $actor);

    /**
     * chat.bsky.moderation.getMessageContext
     *
     * method: get
     */
    public function getMessageContext(string $messageId, ?string $convoId = null, ?int $before = 5, ?int $after = 5);

    /**
     * chat.bsky.moderation.updateActorAccess
     *
     * method: post
     */
    public function updateActorAccess(string $actor, bool $allowAccess, ?string $ref = null);
}
