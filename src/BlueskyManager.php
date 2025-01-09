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
use Revolution\Bluesky\Client\SubClient\BskyClient;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Session\AbstractSession;
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
     * For OAuth authentication.
     *
     * This can also be used to resume LegacySession.
     */
    public function withToken(#[\SensitiveParameter] ?AbstractSession $token): Factory
    {
        if ($token instanceof OAuthSession) {
            $this->agent = OAuthAgent::create($token);
        } elseif ($token instanceof LegacySession) {
            $this->agent = LegacyAgent::create($token);
        }

        return $this;
    }

    /**
     * App password authentication.
     */
    public function login(#[Format('at-identifier')] string $identifier, #[\SensitiveParameter] string $password, ?string $service = null): Factory
    {
        $service = $service ?? $this->entryway();

        $response = $this->client(auth: false)
            ->baseUrl($service.'/xrpc/')
            ->createSession($identifier, $password)
            ->throw();

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
     * Public client.
     *
     * Only `app.bsky.*` APIs can use this public endpoint.
     */
    public function public(): BskyClient
    {
        return Container::getInstance()
            ->make(BskyClient::class)
            ->withHttp(Http::baseUrl($this->publicEndpoint()));
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
            ->when(is_callable($callback), fn (PendingRequest $http) => is_callable($callback) ? $callback($http) : $http)
            ->$method(enum_value($api), $params);
    }

    protected function http(bool $auth = true): PendingRequest
    {
        if (! $auth || ! $this->check() || is_null($this->agent())) {
            return Http::baseUrl($this->entryway('/xrpc/'));
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
        if (empty($this->agent()?->refresh())) {
            return false;
        }

        return ! $this->agent()->tokenExpired();
    }

    public function logout(): Factory
    {
        $this->agent = null;

        return $this;
    }

    public function publicEndpoint(): string
    {
        return Str::rtrim(config('bluesky.public_endpoint'), '/').'/xrpc/';
    }

    public function entryway(?string $path = null): string
    {
        /** @var string $url */
        $url = config('bluesky.service') ?? 'https://bsky.social';
        $url = Str::rtrim($url, '/');

        return $url.$path;
    }
}
