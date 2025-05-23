<?php

/**
 * GENERATED CODE.
 */

declare(strict_types=1);

namespace Revolution\Bluesky\Client\Concerns;

use Illuminate\Http\Client\Response;
use Revolution\AtProto\Lexicon\Contracts\Chat\Bsky\Convo;

trait ChatBskyConvo
{
    public function acceptConvo(string $convoId): Response
    {
        return $this->call(
            api: Convo::acceptConvo,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function addReaction(string $convoId, string $messageId, string $value): Response
    {
        return $this->call(
            api: Convo::addReaction,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function deleteMessageForSelf(string $convoId, string $messageId): Response
    {
        return $this->call(
            api: Convo::deleteMessageForSelf,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getConvo(string $convoId): Response
    {
        return $this->call(
            api: Convo::getConvo,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getConvoAvailability(array $members): Response
    {
        return $this->call(
            api: Convo::getConvoAvailability,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getConvoForMembers(array $members): Response
    {
        return $this->call(
            api: Convo::getConvoForMembers,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getLog(?string $cursor = null): Response
    {
        return $this->call(
            api: Convo::getLog,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function getMessages(string $convoId, ?int $limit = 50, ?string $cursor = null): Response
    {
        return $this->call(
            api: Convo::getMessages,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function leaveConvo(string $convoId): Response
    {
        return $this->call(
            api: Convo::leaveConvo,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function listConvos(?int $limit = 50, ?string $cursor = null, ?string $readState = null, ?string $status = null): Response
    {
        return $this->call(
            api: Convo::listConvos,
            method: self::GET,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function muteConvo(string $convoId): Response
    {
        return $this->call(
            api: Convo::muteConvo,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function removeReaction(string $convoId, string $messageId, string $value): Response
    {
        return $this->call(
            api: Convo::removeReaction,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function sendMessage(string $convoId, array $message): Response
    {
        return $this->call(
            api: Convo::sendMessage,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function sendMessageBatch(array $items): Response
    {
        return $this->call(
            api: Convo::sendMessageBatch,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function unmuteConvo(string $convoId): Response
    {
        return $this->call(
            api: Convo::unmuteConvo,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateAllRead(?string $status = null): Response
    {
        return $this->call(
            api: Convo::updateAllRead,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }

    public function updateRead(string $convoId, ?string $messageId = null): Response
    {
        return $this->call(
            api: Convo::updateRead,
            method: self::POST,
            params: compact($this->params(__METHOD__)),
        );
    }
}
