<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Enums;

enum AtProto: string
{
    case getAuthorFeed = 'app.bsky.feed.getAuthorFeed';
    case getTimeline = 'app.bsky.feed.getTimeline';

    case External = 'app.bsky.embed.external';
    case Images = 'app.bsky.embed.images';

    case createSession = 'com.atproto.server.createSession';
    case createRecord = 'com.atproto.repo.createRecord';
    case uploadBlob = 'com.atproto.repo.uploadBlob';
}
