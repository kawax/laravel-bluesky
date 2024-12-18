<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use BackedEnum;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Container\Container;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Revolution\AtProto\Lexicon\Attributes\Format;
use Revolution\AtProto\Lexicon\Attributes\KnownValues;
use Revolution\Bluesky\Agent\LegacyAgent;
use Revolution\Bluesky\Agent\OAuthAgent;
use Revolution\Bluesky\Client\AtpClient;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Contracts\Factory;
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
    public function withToken(#[\SensitiveParameter] ?OAuthSession $token): Factory
    {
        $this->agent = OAuthAgent::create($token);

        return $this;
    }

    /**
     * App password authentication.
     */
    public function login(#[Format('at-identifier')] string $identifier, #[\SensitiveParameter] string $password): Factory
    {
        $response = $this->client(auth: false)
            ->baseUrl($this->entryway().'/xrpc/')
            ->createSession($identifier, $password);

        $session = LegacySession::create($response->collect());
        $this->agent = LegacyAgent::create($session);

        return $this;
    }

    public function client(bool $auth = true): AtpClient
    {
        return Container::getInstance()
            ->make(AtpClient::class)
            ->withHttp($this->http($auth));
    }

    /**
     * Send any API request.
     *
     * @param  BackedEnum|string  $api  e.g. "app.bsky.actor.getProfile"
     * @param  string  $method  get or post
     * @param  bool  $auth  requires auth
     * @param  ?array  $params  get query or post data
     * @param  null|callable(PendingRequest $http): PendingRequest  $callback  perform processing before sending
     */
    public function send(#[Format('nsid')] BackedEnum|string $api, #[KnownValues(['get', 'post'])] string $method = 'get', bool $auth = true, ?array $params = null, ?callable $callback = null): Response
    {
        $method = Str::lower($method) === 'post' ? 'post' : 'get';

        return $this->http($auth)
            ->when(is_callable($callback), fn (PendingRequest $http) => $callback($http))
            ->$method(enum_value($api), $params);
    }

    protected function http(bool $auth = true): PendingRequest
    {
        if (! $auth || ! $this->check()) {
            return Http::baseUrl($this->publicEndpoint());
        }

        return $this->agent()->http($auth);
    }

    /**
     * @throws AuthenticationException
     */
    public function assertDid(): string
    {
        $did = $this->agent()?->did();

        return empty($did) ? throw new AuthenticationException() : $did;
    }

    public function agent(): ?Agent
    {
        return $this->agent;
    }

    public function withAgent(?Agent $agent): Factory
    {
        $this->agent = $agent;

        return $this;
    }

    public function refreshSession(): Factory
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

    public function logout(): Factory
    {
        $this->agent = null;

        return $this;
    }

    protected function publicEndpoint(): string
    {
        return Str::rtrim(config('bluesky.public_endpoint'), '/').'/xrpc/';
    }

    public function entryway(): string
    {
        return config('bluesky.service') ?? 'https://bsky.social';
    }
}
