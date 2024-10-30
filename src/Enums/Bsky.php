<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Enums;

enum Bsky: string
{
    case Entryway = 'bsky.social';

    case PublicEndpoint = 'https://public.api.bsky.app/xrpc/';

    case getProfile = 'app.bsky.actor.getProfile';

    case getAuthorFeed = 'app.bsky.feed.getAuthorFeed';
    case getTimeline = 'app.bsky.feed.getTimeline';

    case External = 'app.bsky.embed.external';
    case Images = 'app.bsky.embed.images';
}
