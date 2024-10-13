<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Enums;

enum AtProto: string
{
    case createSession = 'com.atproto.server.createSession';
    case refreshSession = 'com.atproto.server.refreshSession';

    case resolveHandle = 'com.atproto.identity.resolveHandle';

    case getAuthorFeed = 'app.bsky.feed.getAuthorFeed';
    case getTimeline = 'app.bsky.feed.getTimeline';

    case createRecord = 'com.atproto.repo.createRecord';
    case uploadBlob = 'com.atproto.repo.uploadBlob';

    case External = 'app.bsky.embed.external';
    case Images = 'app.bsky.embed.images';
}
