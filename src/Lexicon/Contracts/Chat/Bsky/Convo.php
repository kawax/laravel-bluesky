<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Chat\Bsky;

interface Convo
{
    public const deleteMessageForSelf = 'chat.bsky.convo.deleteMessageForSelf';
    public const getConvo = 'chat.bsky.convo.getConvo';
    public const getConvoForMembers = 'chat.bsky.convo.getConvoForMembers';
    public const getLog = 'chat.bsky.convo.getLog';
    public const getMessages = 'chat.bsky.convo.getMessages';
    public const leaveConvo = 'chat.bsky.convo.leaveConvo';
    public const listConvos = 'chat.bsky.convo.listConvos';
    public const muteConvo = 'chat.bsky.convo.muteConvo';
    public const sendMessage = 'chat.bsky.convo.sendMessage';
    public const sendMessageBatch = 'chat.bsky.convo.sendMessageBatch';
    public const unmuteConvo = 'chat.bsky.convo.unmuteConvo';
    public const updateRead = 'chat.bsky.convo.updateRead';

    /**
     * chat.bsky.convo.deleteMessageForSelf
     *
     * method: post
     */
    public function deleteMessageForSelf(string $convoId, string $messageId);

    /**
     * chat.bsky.convo.getConvo
     *
     * method: get
     */
    public function getConvo(string $convoId);

    /**
     * chat.bsky.convo.getConvoForMembers
     *
     * method: get
     */
    public function getConvoForMembers(array $members);

    /**
     * chat.bsky.convo.getLog
     *
     * method: get
     */
    public function getLog(?string $cursor = null);

    /**
     * chat.bsky.convo.getMessages
     *
     * method: get
     */
    public function getMessages(string $convoId, ?int $limit = 50, ?string $cursor = null);

    /**
     * chat.bsky.convo.leaveConvo
     *
     * method: post
     */
    public function leaveConvo(string $convoId);

    /**
     * chat.bsky.convo.listConvos
     *
     * method: get
     */
    public function listConvos(?int $limit = 50, ?string $cursor = null);

    /**
     * chat.bsky.convo.muteConvo
     *
     * method: post
     */
    public function muteConvo(string $convoId);

    /**
     * chat.bsky.convo.sendMessage
     *
     * method: post
     */
    public function sendMessage(string $convoId, array $message);

    /**
     * chat.bsky.convo.sendMessageBatch
     *
     * method: post
     */
    public function sendMessageBatch(array $items);

    /**
     * chat.bsky.convo.unmuteConvo
     *
     * method: post
     */
    public function unmuteConvo(string $convoId);

    /**
     * chat.bsky.convo.updateRead
     *
     * method: post
     */
    public function updateRead(string $convoId, ?string $messageId = null);
}
