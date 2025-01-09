<?php

declare(strict_types=1);

namespace Revolution\Bluesky\Client;

use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use Revolution\Bluesky\Client\SubClient\AdminClient;
use Revolution\Bluesky\Client\SubClient\AtProtoClient;
use Revolution\Bluesky\Client\SubClient\BskyClient;
use Revolution\Bluesky\Client\SubClient\BskyNotification;
use Revolution\Bluesky\Client\SubClient\ChatClient;
use Revolution\Bluesky\Client\SubClient\OzoneClient;
use Revolution\Bluesky\Client\SubClient\SyncClient;
use Revolution\Bluesky\Client\SubClient\VideoClient;
use Revolution\Bluesky\Contracts\XrpcClient;
use Revolution\Bluesky\Facades\Bluesky;

class AtpClient implements XrpcClient
{
    use Macroable {
        Macroable::__call as macroCall;
    }
    use ForwardsCalls;
    use Conditionable;

    use HasHttp;

    /**
     * VideoClient.
     *
     * app.bsky.video
     *
     * @param  string  $token  Service Auth token
     */
    public function video(string $token): VideoClient
    {
        return app(VideoClient::class)->withServiceAuthToken($token);
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
            ->withServiceProxy(ChatClient::CHAT_SERVICE_DID);
    }

    /**
     * AtProtoClient
     *
     * com.atproto
     */
    public function atproto(): AtProtoClient
    {
        return app(AtProtoClient::class)->withHttp($this->http());
    }

    /**
     * BskyClient
     *
     * app.bsky
     */
    public function bsky(): BskyClient
    {
        return app(BskyClient::class)
            ->withHttp($this->http())
            ->when(empty(data_get($this->http()->getOptions(), 'headers.Authorization')), function ($client) {
                $client->baseUrl(Bluesky::publicEndpoint());
            });
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
     * AdminClient.
     *
     * com.atproto.admin
     */
    public function admin(): AdminClient
    {
        return app(AdminClient::class)->withHttp($this->http());
    }

    /**
     * SyncClient.
     *
     * com.atproto.sync
     */
    public function sync(): SyncClient
    {
        return app(SyncClient::class)->withHttp($this->http());
    }

    /**
     * OzoneClient.
     *
     * tools.ozone
     */
    public function ozone(): OzoneClient
    {
        return app(OzoneClient::class)->withHttp($this->http());
    }

    /**
     * Dynamically proxy other methods to the underlying response.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            $this->macroCall($method, $parameters);
        }

        if (method_exists(AtProtoClient::class, $method)) {
            return $this->forwardCallTo($this->atproto(), $method, $parameters);
        }

        if (method_exists(BskyClient::class, $method)) {
            return $this->forwardCallTo($this->bsky(), $method, $parameters);
        }

        static::throwBadMethodCallException($method);
    }
}
