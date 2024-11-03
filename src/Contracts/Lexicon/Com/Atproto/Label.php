<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts\Lexicon\Com\Atproto;

interface Label
{
    /**
     * Find labels relevant to the provided AT-URI patterns. Public endpoint for moderation services, though may return different or additional results with auth.
     *
     * method: get
     */
    public function queryLabels(array $uriPatterns, ?array $sources = null, ?int $limit = 50, ?string $cursor = null);
}
