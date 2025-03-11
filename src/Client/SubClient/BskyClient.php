<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client\SubClient;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Actor;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Feed;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Graph;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Labeler;
use Revolution\Bluesky\Client\Concerns\AppBskyActor;
use Revolution\Bluesky\Client\Concerns\AppBskyFeed;
use Revolution\Bluesky\Client\Concerns\AppBskyGraph;
use Revolution\Bluesky\Client\Concerns\AppBskyLabeler;
use Revolution\Bluesky\Client\HasHttp;
use Revolution\Bluesky\Contracts\XrpcClient;

class BskyClient implements
    XrpcClient,
    Actor,
    Feed,
    Graph,
    Labeler
{
    use Macroable;
    use Conditionable;

    use HasHttp;

    use AppBskyActor;
    use AppBskyFeed;
    use AppBskyGraph;
    use AppBskyLabeler;

    public const APPVIEW_SERVICE_DID = 'did:web:api.bsky.app#bsky_appview';
}
