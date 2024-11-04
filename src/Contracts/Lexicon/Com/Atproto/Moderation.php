<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts\Lexicon\Com\Atproto;

interface Moderation
{
    /**
     * Submit a moderation report regarding an atproto account or record. Implemented by moderation services (with PDS proxying), and requires auth.
     *
     * method: post
     */
    public function createReport(string $reasonType, array $subject, ?string $reason = null);
}
