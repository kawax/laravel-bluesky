<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Enums;

enum AtProto: string
{
    case createAccount = 'com.atproto.server.createAccount';

    case createSession = 'com.atproto.server.createSession';
    case refreshSession = 'com.atproto.server.refreshSession';

    case resolveHandle = 'com.atproto.identity.resolveHandle';

    case createRecord = 'com.atproto.repo.createRecord';
    case uploadBlob = 'com.atproto.repo.uploadBlob';
}
