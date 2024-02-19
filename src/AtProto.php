<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

enum AtProto: string
{
    case getAuthorFeed = 'app.bsky.feed.getAuthorFeed';
    case getTimeline = 'app.bsky.feed.getTimeline';

    case createSession = 'com.atproto.server.createSession';
    case createRecord = 'com.atproto.repo.createRecord';
}
