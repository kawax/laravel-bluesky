<?php

namespace Revolution\Bluesky\FeedGenerator\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WellKnownDidController
{
    public function __invoke(Request $request): array
    {
        return [
            '@context' => ['https://www.w3.org/ns/did/v1'],
            'id' => config('bluesky.generator.service') ?? 'did:web:'.Str::of(url('/'))->rtrim('/')->chopStart(['http://', 'https://'])->toString(),
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
