<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Actor;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Feed;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Graph;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Identity;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Repo;
use Revolution\Bluesky\Client\Concerns\AppBskyActor;
use Revolution\Bluesky\Client\Concerns\AppBskyFeed;
use Revolution\Bluesky\Client\Concerns\AppBskyGraph;
use Revolution\Bluesky\Client\Concerns\AppBskyLabeler;
use Revolution\Bluesky\Client\Concerns\AppBskyNotification;
use Revolution\Bluesky\Client\Concerns\AppBskyUnspecced;
use Revolution\Bluesky\Client\Concerns\AppBskyVideo;
use Revolution\Bluesky\Client\Concerns\ComAtprotoAdmin;
use Revolution\Bluesky\Client\Concerns\ComAtprotoIdentity;
use Revolution\Bluesky\Client\Concerns\ComAtprotoLabel;
use Revolution\Bluesky\Client\Concerns\ComAtprotoModeration;
use Revolution\Bluesky\Client\Concerns\ComAtprotoRepo;
use Revolution\Bluesky\Client\Concerns\ComAtprotoServer;
use Revolution\Bluesky\Client\Concerns\ComAtprotoSync;
use Revolution\Bluesky\Client\Concerns\ComAtprotoTemp;
use Revolution\Bluesky\Contracts\XrpcClient;

class AtpClient implements XrpcClient, Repo, Identity, Actor, Feed, Graph
{
    use Macroable;
    use Conditionable;

    use AppBskyActor;
    use AppBskyFeed;
    use AppBskyGraph {
        AppBskyGraph::getBlocks insteadof ComAtprotoSync;
        ComAtprotoSync::getBlocks as getBlocksSync;
    }
    use AppBskyLabeler;
    use AppBskyNotification {
        AppBskyActor::putPreferences insteadof AppBskyNotification;
        AppBskyNotification::putPreferences as putPreferencesNotification;
    }
    use AppBskyUnspecced;
    use AppBskyVideo;

    use ComAtprotoAdmin {
        ComAtprotoServer::deleteAccount insteadof ComAtprotoAdmin;
        ComAtprotoAdmin::deleteAccount as deleteAccountAdmin;
    }

    use ComAtprotoIdentity;
    use ComAtprotoLabel;
    use ComAtprotoModeration;
    use ComAtprotoRepo;
    use ComAtprotoServer;
    use ComAtprotoSync {
        ComAtprotoRepo::getRecord insteadof ComAtprotoSync;
        ComAtprotoSync::getRecord as getRecordSync;
    }
    use ComAtprotoTemp;
}
