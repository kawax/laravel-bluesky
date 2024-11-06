<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use BackedEnum;
use Illuminate\Container\Container;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\Bluesky\Agent\LegacyAgent;
use Revolution\Bluesky\Agent\OAuthAgent;
use Revolution\Bluesky\Client\AtpClient;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Contracts\XrpcClient;
use Revolution\Bluesky\Session\LegacySession;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Support\Identity;
use Revolution\Bluesky\Support\PDS;

use function Illuminate\Support\enum_value;

class BlueskyManager implements Factory
{
    use HasShortHand;
    use Macroable;
    use Conditionable;

    protected ?Agent $agent = null;

    /**
     * OAuth authentication.
     */
    public function withToken(#[\SensitiveParameter] ?OAuthSession $token): self
    {
        $this->agent = OAuthAgent::create($token);

        return $this;
    }

    /**
     * App password authentication.
     */
    public function login(string $identifier, #[\SensitiveParameter] string $password): self
    {
        $response = $this->client(auth: false)
            ->withHttp(Http::baseUrl($this->entryway().'/xrpc/'))
            ->createSession($identifier, $password);

        $session = LegacySession::create($response->collect());
        $this->agent = LegacyAgent::create($session);

        return $this;
    }

    public function agent(): ?Agent
    {
        return $this->agent;
    }

    public function withAgent(?Agent $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function http(bool $auth = true): PendingRequest
    {
        if (! $auth || ! $this->check()) {
            return Http::baseUrl($this->publicEndpoint());
        }

        return $this->agent()->http($auth);
    }

    /**
     * Send any API request.
     *
     * @param  BackedEnum|string  $api  e.g. "app.bsky.actor.getProfile"
     * @param  string  $method  get or post.
     * @param  bool  $auth  Requires auth.
     * @param  ?array  $params  get query or post data.
     */
    public function send(BackedEnum|string $api, string $method = 'get', bool $auth = true, ?array $params = null): Response
    {
        return $this->http($auth)->$method(enum_value($api), $params);
    }

    public function client(bool $auth = false): XrpcClient|AtpClient
    {
        return Container::getInstance()
            ->make(XrpcClient::class)
            ->withHttp($this->http($auth));
    }

    public function refreshSession(): self
    {
        $this->agent = $this->agent()?->refreshSession();

        return $this;
    }

    public function identity(): Identity
    {
        return Container::getInstance()->make(Identity::class);
    }

    public function pds(): PDS
    {
        return Container::getInstance()->make(PDS::class);
    }

    public function check(): bool
    {
        return ! empty($this->agent()?->refresh());
    }

    public function logout(): self
    {
        $this->agent = null;

        return $this;
    }

    public function publicEndpoint(): string
    {
        return Str::rtrim(config('bluesky.public_endpoint'), '/').'/xrpc/';
    }

    public function entryway(): string
    {
        return config('bluesky.service') ?? 'https://bsky.social';
    }
}
