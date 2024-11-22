<?php

namespace Revolution\Bluesky\FeedGenerator\Http;

use Illuminate\Support\Str;
use Revolution\Bluesky\Support\DID;

class WellKnownDidController
{
    public function __invoke(): array
    {
        return [
            '@context' => ['https://www.w3.org/ns/did/v1'],
            'id' => config('bluesky.generator.service') ?? DID::web(),
            'service' => [
                [
                    'id' => '#bsky_fg',
                    'type' => 'BskyFeedGenerator',
                    'serviceEndpoint' => Str::rtrim(url('/'), '/'),
                ],
            ],
        ];
    }
}
