<?php

namespace Revolution\Bluesky\FeedGenerator\Http;

use Illuminate\Http\Request;
use Revolution\Bluesky\FeedGenerator\FeedGenerator;

class DescribeController
{
    public function __invoke(Request $request): array
    {
        return FeedGenerator::describeFeedGenerator(
            publisher: config('bluesky.generator.publisher'),
            service: config('bluesky.generator.service'),
        );
    }
}
