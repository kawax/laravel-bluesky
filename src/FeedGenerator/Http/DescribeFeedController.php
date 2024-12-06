<?php

declare(strict_types=1);

namespace Revolution\Bluesky\FeedGenerator\Http;

use Illuminate\Http\Request;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;

class DescribeFeedController
{
    public function __invoke(Request $request): array
    {
        return FeedGenerator::describeFeedGenerator();
    }
}
