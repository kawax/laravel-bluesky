<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts\Lexicon\App\Bsky;

interface Labeler
{
    /**
     * Get information about a list of labeler services.
     *
     * method: get
     */
    public function getServices(array $dids, ?bool $detailed = null);
}
