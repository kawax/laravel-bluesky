<?php

namespace Revolution\Bluesky\Client;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Actor;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Feed;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Graph;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Labeler;
use Revolution\AtProto\Lexicon\Contracts\App\Bsky\Unspecced;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Identity;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Label;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Moderation;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Repo;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Server;
use Revolution\AtProto\Lexicon\Contracts\Com\Atproto\Temp;
use Revolution\Bluesky\Client\Concerns\AppBskyActor;
use Revolution\Bluesky\Client\Concerns\AppBskyFeed;
use Revolution\Bluesky\Client\Concerns\AppBskyGraph;
use Revolution\Bluesky\Client\Concerns\AppBskyLabeler;
use Revolution\Bluesky\Client\Concerns\AppBskyUnspecced;
use Revolution\Bluesky\Client\Concerns\ComAtprotoIdentity;
use Revolution\Bluesky\Client\Concerns\ComAtprotoLabel;
use Revolution\Bluesky\Client\Concerns\ComAtprotoModeration;
use Revolution\Bluesky\Client\Concerns\ComAtprotoRepo;
use Revolution\Bluesky\Client\Concerns\ComAtprotoServer;
use Revolution\Bluesky\Client\Concerns\ComAtprotoTemp;
use Revolution\Bluesky\Client\Substitute\AtprotoAdmin;
use Revolution\Bluesky\Client\Substitute\SyncClient;
use Revolution\Bluesky\Client\Substitute\BskyNotification;
use Revolution\Bluesky\Contracts\XrpcClient;

class AtpClient implements XrpcClient,
    Actor, Feed, Graph, Labeler, Unspecced,
    Identity, Label, Moderation, Repo, Server, Temp
{
    use Macroable;
    use Conditionable;

    use HasHttp;

    // app.bsky
    use AppBskyActor;
    use AppBskyFeed;
    use AppBskyGraph;
    use AppBskyLabeler;
    use AppBskyUnspecced;

    // com.atproto
    use ComAtprotoIdentity;
    use ComAtprotoLabel;
    use ComAtprotoModeration;
    use ComAtprotoRepo;
    use ComAtprotoServer;
    use ComAtprotoTemp;

    /**
     * VideoClient.
     *
     * app.bsky.video
     *
     * @param  string  $token  Service Auth token
     */
    public function video(string $token): VideoClient
    {
        $http = Http::baseUrl('https://video.bsky.app/xrpc/')
            ->withToken($token);

        return app(VideoClient::class)->withHttp($http);
    }

    /**
     * ChatClient.
     *
     * chat.bsky
     */
    public function chat(): ChatClient
    {
        return app(ChatClient::class)
            ->withHttp($this->http())
            ->withServiceProxy('did:web:api.bsky.chat#bsky_chat');
    }

    /**
     * Some methods are conflicting. Separate them into other class.
     *
     * app.bsky.notification
     */
    public function notification(): BskyNotification
    {
        return app(BskyNotification::class)->withHttp($this->http());
    }

    /**
     * com.atproto.admin
     */
    public function admin(): AtprotoAdmin
    {
        return app(AtprotoAdmin::class)->withHttp($this->http());
    }

    /**
     * com.atproto.sync
     */
    public function sync(): SyncClient
    {
        return app(SyncClient::class)->withHttp($this->http());
    }
}
