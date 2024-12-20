<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Contracts;

use Illuminate\Http\Request;

interface FeedGeneratorAlgorithm
{
    public function __invoke(int $limit, ?string $cursor, ?string $user, Request $request): array;
}
