<?php

declare(strict_types=1);

namespace Revolution\Bluesky;

use Illuminate\Container\Container;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Revolution\Bluesky\Agent\LegacyAgent;
use Revolution\Bluesky\Agent\OAuthAgent;
use Revolution\Bluesky\Client\AtpClient;
use Revolution\Bluesky\Client\BskyClient;
use Revolution\Bluesky\Contracts\Agent;
use Revolution\Bluesky\Contracts\Factory;
use Revolution\Bluesky\Lexicon\Enum\AtProto;
use Revolution\Bluesky\Lexicon\Enum\Bsky;
use Revolution\Bluesky\Notifications\BlueskyMessage;
use Revolution\Bluesky\Session\LegacySession;
use Revolution\Bluesky\Session\OAuthSession;
use Revolution\Bluesky\Support\Identity;
use Revolution\Bluesky\Support\PDS;

use function Illuminate\Support\enum_value;

class BlueskyClient implements Factory
{
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
        $response = Http::baseUrl($this->entryway().'/xrpc/')
            ->post(AtProto::createSession->value, [
                'identifier' => $identifier,
                'password' => $password,
            ]);

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
     * @param  AtProto|Bsky|string  $api  e.g. "app.bsky.actor.getProfile"
     * @param  string  $method  get or post.
     * @param  bool  $auth  Requires auth.
     * @param  ?array  $params  get query or post data.
     */
    public function send(AtProto|Bsky|string $api, string $method = 'get', bool $auth = true, ?array $params = null): Response
    {
        return $this->http($auth)->$method(enum_value($api), $params);
    }

    protected function bsky(bool $auth = false): BskyClient
    {
        return Container::getInstance()
            ->make(BskyClient::class)
            ->withHttp($this->http($auth));
    }

    protected function atp(bool $auth = true): AtpClient
    {
        return Container::getInstance()
            ->make(AtpClient::class)
            ->withHttp($this->http($auth));
    }

    /**
     * @param  string|null  $actor  DID or handle.
     */
    public function profile(?string $actor = null): Response
    {
        return $this->bsky(auth: false)->getProfile(
            actor: $actor ?? $this->agent()?->did() ?? '',
        );
    }

    /**
     * getAuthorFeed.
     *
     * @param  string|null  $actor  DID or handle.
     */
    public function feed(?string $actor = null, int $limit = 50, string $cursor = '', string $filter = 'posts_with_replies'): Response
    {
        return $this->bsky(auth: false)->getAuthorFeed(
            actor: $actor ?? $this->agent()?->did() ?? '',
            limit: $limit,
            cursor: $cursor,
            filter: $filter,
        );
    }

    /**
     * My timeline.
     */
    public function timeline(int $limit = 50, string $cursor = ''): Response
    {
        return $this->bsky(auth: true)->getTimeline(
            limit: $limit,
            cursor: $cursor,
        );
    }

    public function createRecord(string $repo, string $collection, array $record): Response
    {
        return $this->atp(auth: true)->createRecord(
            repo: $repo,
            collection: $collection,
            record: $record,
        );
    }

    /**
     * Create new post.
     */
    public function post(string|BlueskyMessage $text): Response
    {
        $message = $text instanceof BlueskyMessage ? $text : BlueskyMessage::create($text);

        $record = collect($message->toArray())
            ->put('createdAt', now()->toISOString())
            ->reject(fn ($item) => blank($item))
            ->toArray();

        return $this->createRecord(
            repo: $this->agent()->did(),
            collection: 'app.bsky.feed.post',
            record: $record,
        );
    }

    /**
     * Upload blob.
     *
     * @throws ConnectionException
     */
    public function uploadBlob(mixed $data, string $type = 'image/png'): Response
    {
        return $this->http()
            ->withBody($data, $type)
            ->post(AtProto::uploadBlob->value);
    }

    /**
     * Upload video.
     *
     * @throws ConnectionException
     */
    public function uploadVideo(mixed $data, string $type = 'video/mp4'): Response
    {
        return $this->http()
            ->withBody($data, $type)
            ->post(Bsky::uploadVideo->value);
    }

    public function refreshSession(): self
    {
        $this->agent = $this->agent()?->refreshSession();

        return $this;
    }

    /**
     * @param  string  $handle  e.g. "alice.test"
     */
    public function resolveHandle(string $handle): Response
    {
        if (! Identity::isHandle($handle)) {
            throw new InvalidArgumentException("The handle '$handle' is not a valid handle.");
        }

        return $this->atp(auth: false)->resolveHandle(handle: $handle);
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
