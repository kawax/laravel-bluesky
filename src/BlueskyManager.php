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
use Revolution\Bluesky\Client\VideoClient;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Contracts\Recordable;
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
    public function login(#[Format('at-identifier')] string $identifier, #[\SensitiveParameter] string $password): self
    {
        $response = $this->client(auth: false)
            ->withHttp(Http::baseUrl($this->entryway().'/xrpc/'))
            ->createSession($identifier, $password);

        $session = LegacySession::create($response->collect());
        $this->agent = LegacyAgent::create($session);

        return $this;
    }

    public function client(bool $auth = true): XrpcClient|AtpClient
    {
        return Container::getInstance()
            ->make(XrpcClient::class)
            ->withHttp($this->http($auth));
    }

    /**
     * Send any API request.
     *
     * @param  BackedEnum|string  $api  e.g. "app.bsky.actor.getProfile"
     * @param  string  $method  get or post.
     * @param  bool  $auth  Requires auth.
     * @param  ?array  $params  get query or post data.
     * @param  null|callable(PendingRequest $http): PendingRequest  $callback  Perform processing before sending.
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
     * Client for video.
     */
    protected function video(string $token): VideoClient
    {
        $http = Http::baseUrl('https://video.bsky.app/xrpc/')
            ->withToken($token);

        return Container::getInstance()
            ->make(VideoClient::class)
            ->withHttp($http);
    }

    public function withServiceAuth(?string $token = null): static
    {
        $this->agent = $this->agent()->withServiceAuth($token);

        return $this;
    }

    public function withoutServiceAuth(): static
    {
        $this->agent = $this->agent()->withoutServiceAuth();

        return $this;
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

    public function withAgent(?Agent $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function createRecord(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, Recordable|array $record, ?string $rkey = null, ?bool $validate = null, ?string $swapCommit = null): Response
    {
        $record = $record instanceof Recordable ? $record->toRecord() : $record;

        return $this->client(auth: true)
            ->createRecord(
                repo: $repo,
                collection: $collection,
                record: $record,
                rkey: $rkey,
                validate: $validate,
                swapCommit: $swapCommit,
            );
    }

    public function getRecord(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, string $rkey, #[Format('cid')] ?string $cid = null): Response
    {
        return $this->client(auth: true)
            ->getRecord(
                repo: $repo,
                collection: $collection,
                rkey: $rkey,
                cid: $cid,
            );
    }

    public function listRecords(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, ?int $limit = 50, ?string $cursor = null, ?bool $reverse = null): Response
    {
        return $this->client(auth: true)
            ->listRecords(
                repo: $repo,
                collection: $collection,
                limit: $limit,
                cursor: $cursor,
                reverse: $reverse,
            );
    }

    public function putRecord(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, string $rkey, Recordable|array $record, ?bool $validate = null, #[Format('cid')] ?string $swapRecord = null, #[Format('cid')] ?string $swapCommit = null): Response
    {
        $record = $record instanceof Recordable ? $record : $record->toRecord();

        return $this->client(auth: true)
            ->putRecord(
                repo: $repo,
                collection: $collection,
                rkey: $rkey,
                record: $record,
                validate: $validate,
                swapRecord: $swapRecord,
                swapCommit: $swapCommit,
            );
    }

    public function deleteRecord(#[Format('at-identifier')] string $repo, #[Format('nsid')] string $collection, string $rkey, #[Format('cid')] ?string $swapRecord = null, #[Format('cid')] ?string $swapCommit = null): Response
    {
        return $this->client(auth: true)
            ->deleteRecord(
                repo: $repo,
                collection: $collection,
                rkey: $rkey,
                swapRecord: $swapRecord,
                swapCommit: $swapCommit,
            );
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

    protected function publicEndpoint(): string
    {
        return Str::rtrim(config('bluesky.public_endpoint'), '/').'/xrpc/';
    }

    public function entryway(): string
    {
        return config('bluesky.service') ?? 'https://bsky.social';
    }
}
