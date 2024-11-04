<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Lexicon\Contracts\Chat\Bsky;

interface Actor
{
    public const deleteAccount = 'chat.bsky.actor.deleteAccount';
    public const exportAccountData = 'chat.bsky.actor.exportAccountData';

    /**
     * chat.bsky.actor.deleteAccount
     *
     * method: post
     */
    public function deleteAccount();

    /**
     * chat.bsky.actor.exportAccountData
     *
     * method: get
     */
    public function exportAccountData();
}
