<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Actor;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Feed;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Graph;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Labeler;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Unspecced;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Video;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Identity;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Label;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Moderation;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Repo;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Server;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Sync;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Temp;
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

final class AtpClient implements XrpcClient,
    Actor, Feed, Graph, Labeler, Unspecced, Video,
    Identity, Label, Moderation, Repo, Server, Temp
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
