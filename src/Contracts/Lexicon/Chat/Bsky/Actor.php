<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts\Lexicon\Chat\Bsky;

interface Actor
{
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
